<?php

namespace App\Repositories\Eloquent;

use App\Models\CarType;
use App\Models\Driver;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideRepository implements RideRepositoryInterface
{

    //------------Ride\Rider-----------//
    public function estimateFare(array $data)
    {
        $distanceKm = $this->calculateDistanceKm(
            $data['pickup_lat'],
            $data['pickup_lng'],
            $data['drop_lat'],
            $data['drop_lng'],
        );
        $avgSpeedKmPerMinute = 0.5;
        $durationMinutes = $distanceKm > 0 ? $distanceKm / $avgSpeedKmPerMinute : 1;

        $cartype = CarType::query()->get();
        $estimates = $cartype->map(function (CarType $cartype) use ($distanceKm, $durationMinutes) {
            $fare = $cartype->base_fare
                + ($distanceKm * $cartype->per_km)
                + ($durationMinutes * $cartype->per_min);
            $fare = max($fare, $cartype->min_fare);
            return [
                'car_type_id'      => $cartype->id,
                'name'             => $cartype->name,
                'icon'             => $cartype->icon,
                'capacity'         => $cartype->capacity,
                'distance_km'      => round($distanceKm, 2),
                'duration_minutes' => round($durationMinutes),
                'estimated_fare'   => round($fare, 2),
            ];
        });
        return $estimates->toArray();
    }


    private function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    public function findForRider(int $rideId, int $rider_id): ?Ride
    {
        return Ride::with(['driver.user', 'carType'])
            ->where('id', $rideId)
            ->where('rider_id', $rider_id)   // مهم: الراكب ما يشوفش رحلة غيره
            ->first();
    }


    public function paginateForRider(int $rider_id, int $perPage = 15, ?string $status = null): LengthAwarePaginator
    {
        return Ride::query()
            ->where('rider_id', $rider_id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['driver.user:id,name,phone_number', 'carType:id,type'])
            ->latest()
            ->paginate($perPage);
    }


    public function createRide(int $riderId, array $data): Ride
    {
        $estimate = collect($this->estimateFare($data))
            ->firstWhere('car_type_id', $data['car_type_id']);

        if (! $estimate) {
            throw ValidationException::withMessages([
                'car_type_id' => 'Selected car type is not available for this trip.',
            ]);
        }

        return DB::transaction(function () use ($riderId, $data, $estimate) {
            $ride = Ride::create([
                'rider_id'       => $riderId,
                'car_type_id'    => $data['car_type_id'],
                'status'         => 'searching',

                'pickup_lat'     => $data['pickup_lat'],
                'pickup_lng'     => $data['pickup_lng'],
                'pickup_address' => $data['pickup_address'] ?? null,

                'drop_lat'       => $data['drop_lat'],
                'drop_lng'       => $data['drop_lng'],
                'drop_address'   => $data['drop_address'] ?? null,

                'estimated_fare' => $estimate['estimated_fare'],
                'payment_method' => $data['payment_method'],
            ]);

            ride_status_log::create([
                'ride_id'    => $ride->id,
                'status'     => 'searching',
                'changed_by' => 'system',
                'note'       => 'Ride created',
            ]);

            return $ride;
        });
    }








    //------Ride/Driver-------//
    public function acceptRide(int $rideId, int $driverId): Ride
    {
        return DB::transaction(function () use ($rideId, $driverId) {
            // lock the row so two drivers can't accept the same ride
            $ride = Ride::where('id', $rideId)
                ->where('status', 'searching')
                ->lockForUpdate()
                ->firstOrFail();

            $ride->update([
                'driver_id'   => $driverId,
                'status'      => 'accepted',
                'accepted_at' => now(),
            ]);

            $this->log($ride->id, 'accepted', $driverId);

            return $ride;
        });
    }

    public function rejectRide(int $rideId, int $driverId): void
    {
        $ride = Ride::findOrFail($rideId);

        $key      = "ride:{$ride->id}:excluded_drivers";
        $excluded = Cache::get($key, []);
        $excluded[] = $driverId;
        Cache::put($key, array_unique($excluded), now()->addMinutes(2));

        $this->log($ride->id, 'driver_rejected', $driverId);
    }

    public function markArrived(int $rideId, int $driverId): Ride
    {
        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driverId)
            ->where('status', 'accepted')
            ->firstOrFail();

        $ride->update([
            'status'  => 'arrived',
            'arrived' => now(),
        ]);

        $this->log($ride->id, 'driver_arrived', $driverId);

        return $ride;
    }

    public function startRide(int $rideId, int $driverId): Ride
    {
        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driverId)
            ->where('status', 'arrived')
            ->firstOrFail();

        $ride->update([
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);

        $this->log($ride->id, 'Ride_Started', $driverId);

        return $ride;
    }

    public function completeRide(int $rideId, int $driverId): Ride
    {
        $ride = Ride::where('id', $rideId)
            ->where('driver_id', $driverId)
            ->where('status', 'in_progress')
            ->firstOrFail();

        $commissionRate = 0.2;
        $fare           = $ride->estimated_fare;
        $finalFare      = $fare - ($fare * $commissionRate);

        $ride->update([
            'status'     => 'completed',
            'final_fare' => $finalFare,
            'ended_at'   => now(),
        ]);

        $this->log($ride->id, 'Ride_Completed', $driverId);

        return $ride;
    }

    private function log(int $rideId, string $status, int $driverId): void
    {
        ride_status_log::create([
            'ride_id'    => $rideId,
            'status'     => $status,
            'changed_by' => 'driver',
            'note'       => "driver_id:{$driverId}",
        ]);
    }
}
