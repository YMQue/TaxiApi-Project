<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\RatingException;
use App\Models\CarType;
use App\Models\Driver;
use App\Models\Rating;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RideRepository implements RideRepositoryInterface
{
    private WalletService $wallet;
    private SettingRepositoryInterface $setting;
    private NotificationService $notifications;


    public function __construct(WalletService $wallet, SettingRepositoryInterface $setting, NotificationService $notifications)
    {
        $this->wallet = $wallet;
        $this->setting = $setting;
        $this->notifications = $notifications;
    }

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
        $activeRide = Ride::where('rider_id', $riderId)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->exists();

        if ($activeRide) {                    // التحقق من ان كان الراكب عنده رحلة شغالة
            throw ValidationException::withMessages([
                'ride' => 'You already have an active ride. Please complete or cancel it before requesting a new one.',
            ]);
        }
        $estimate = collect($this->estimateFare($data))
            ->firstWhere('car_type_id', $data['car_type_id']);

        if (! $estimate) {
            throw ValidationException::withMessages([
                'car_type_id' => 'Selected car type is not available for this trip.',
            ]);
        }

        $wallet = $this->wallet->getBalance($riderId, 'rider');

        if ($data['payment_method'] === 'wallet') {                           // wallet balance check
            if ($wallet['balance'] < $estimate['estimated_fare']) {
                throw ValidationException::withMessages([
                    'method' => 'Insufficient wallet balance for this ride.',
                ]);
            }
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

    public function rate(int $rideId, string $role, int $actorId, int $rating, ?string $comment = null): Rating
    {
        $isRider = $role === 'rider';

        return DB::transaction(function () use ($rideId, $isRider, $actorId, $rating, $comment) {
            $ride = Ride::where('id', $rideId)
                ->where($isRider ? 'rider_id' : 'driver_id', $actorId)
                ->lockForUpdate()
                ->first();

            if (! $ride) {
                throw RatingException::notFound();
            }

            if ($ride->status !== 'completed') {
                throw RatingException::notCompleted();
            }

            $record = Rating::firstOrNew(['ride_id' => $ride->id]);

            $ratingCol  = $isRider ? 'rider_rating'  : 'driver_rating';
            $commentCol = $isRider ? 'rider_comment' : 'driver_comment';

            if ($record->{$ratingCol} !== null) {
                throw RatingException::alreadyRated();
            }

            $record->fill([
                'rider_id'  => $ride->rider_id,
                'driver_id' => $ride->driver_id,
                $ratingCol  => $rating,
                $commentCol => $comment,
            ])->save();

            if ($isRider) {
                $avg = Rating::where('driver_id', $ride->driver_id)
                    ->whereNotNull('rider_rating')
                    ->avg('rider_rating');

                Driver::where('id', $ride->driver_id)
                    ->update(['rate' => round($avg, 2)]);
            }

            return $record;
        });
    }








    //------Ride/Driver-------//
    public function acceptRide(int $rideId, int $driverId): Ride
    {
        $ride = DB::transaction(function () use ($rideId, $driverId) {
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
        $ride->load('rider', 'driver.user');

        $this->notifications->send(
            $ride->rider,
            'ride_accepted',
            'Driver on the way',
            "{$ride->driver->user->name} accepted your ride and is heading to you.",
            ['ride_id' => $ride->id],
        );

        return $ride;
    }

    public function rejectRide(int $rideId, int $driverId): void
    {
        $ride = Ride::findOrFail($rideId);

        $key      = "ride:{$ride->id}:excluded_drivers";
        $excluded = Cache::get($key, []);
        $excluded[] = $driverId;
        Cache::put($key, array_unique($excluded), now()->addMinutes(10));

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

        $this->notifications->send(
        $ride->rider,
        'ride_arrived',
        'Your driver has arrived',
        'Your driver is waiting at the pickup point.',
        ['ride_id' => $ride->id],
    );


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
        $rider = $ride->rider_id;
        $method = $ride->payment_method;

        $commissionRate = $this->setting->get('commission_rate');
        $fare           = $ride->estimated_fare;
        $finalFare      = $fare - ($fare * $commissionRate);



        if ($method === 'wallet') {
            $this->wallet->credit($driverId, $finalFare, 'driver', 'Driver earning');
            $this->wallet->debit($rider, $fare, 'rider', 'Ride Payment');
        }


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

    public function cancelRide(int $rideId, string $by, int $actorId, ?string $reason = null): Ride
{
    $ride = DB::transaction(function () use ($rideId, $by, $actorId, $reason) {
        $ride = Ride::whereKey($rideId)->lockForUpdate()->firstOrFail();

        $owns = $by === 'rider'
            ? $ride->rider_id === $actorId
            : $ride->driver_id === $actorId;

        if (! $owns) {
            throw new BusinessRuleException('Ride not found', 404);
        }

        $cancellable = $by === 'rider'
            ? ['searching', 'accepted', 'arrived']
            : ['accepted', 'arrived'];

        if (! in_array($ride->status, $cancellable, true)) {
            throw new BusinessRuleException('This ride can no longer be cancelled.', 409);
        }

        $fee     = $this->cancellationFee($ride);
        $charged = $fee > 0 ? $this->chargeFee($ride, $by, $fee) : 0.0;

        if ($by === 'rider') {
            $ride->update([
                'status'        => 'cancelled',
                'cancelled_by'  => 'rider',
                'cancel_reason' => $reason,
                'cancelled_at'  => now(),
            ]);
            $logStatus = 'cancelled';
        } else {
            // driver cancelled
            $ride->update([
                'status'        => 'cancelled',
                'driver_id'     => $actorId,
                'accepted_at'   => null,
                'cancel_reason' => $reason,
            ]);
            $logStatus = 'driver_cancelled';
        }

        ride_status_log::create([
            'ride_id'    => $ride->id,
            'status'     => $logStatus,
            'changed_by' => $by,
            'note'       => $reason,
        ]);

        $ride = $ride->fresh();
        $ride->cancellation_fee = $charged; // dynamic attribute, not saved

        return $ride;
    });

    // transaction committed: now notify the other party
    $ride->load('rider', 'driver.user');
    $fee = (float) $ride->cancellation_fee;

    if ($by === 'rider') {
        // driver_id is still set, so tell the driver (nobody if the ride was still searching)
        if ($ride->driver) {
            $this->notifications->send(
                $ride->driver->user,
                'ride_cancelled',
                'Ride cancelled',
                'The rider cancelled the ride.'
            );
        }
    } else {
        $this->notifications->send(
            $ride->rider,
            'ride_cancelled',
            'Driver cancelled',
            'Your driver cancelled.',
            ['ride_id' => $ride->id, 'status' => $ride->status],
        );
    }

    return $ride;
}

private function cancellationFee(Ride $ride): float
{
    if ($ride->status === 'searching') {
        return 0.0;
    }

    return (float) $this->setting->get('cancellation_fee', 5);
}

private function chargeFee(Ride $ride, string $by, float $fee): float
{
    $driver = $ride->driver;

    $payerId   = $by === 'rider' ? $ride->rider_id : $driver->user_id;
    $payerType = $by;

    $balance = (float) $this->wallet->getWallet($payerId, $payerType)->balance;
    $fee     = min($fee, $balance);

    if ($fee <= 0) {
        return 0.0;
    }

    $this->wallet->debit($payerId, $fee, $payerType, "Cancellation fee for ride #{$ride->id}", $ride);

    if ($by === 'rider') {
        $this->wallet->credit($driver->user_id, $fee, 'driver', "Cancellation compensation for ride #{$ride->id}", $ride);
    }

    return $fee;
}
}
