<?php

namespace App\Repositories\Eloquent;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\PromoCodeException;
use App\Exceptions\RatingException;
use App\Models\CarType;
use App\Models\Driver;
use App\Models\Rating;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use App\Services\NotificationService;
use App\Services\PromoCodeService;
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
    private PromoCodeService $promos;



    public function __construct(WalletService $wallet, SettingRepositoryInterface $setting, NotificationService $notifications, PromoCodeService $promos)
    {
        $this->wallet = $wallet;
        $this->setting = $setting;
        $this->notifications = $notifications;
        $this->promos = $promos;
    }

    //------------Ride\Rider-----------//
    public function estimateFare(array $data, ?int $riderId = null)
    {
        $distanceKm = $this->calculateDistanceKm(
            $data['pickup_lat'],
            $data['pickup_lng'],
            $data['drop_lat'],
            $data['drop_lng'],
        );
        $avgSpeedKmPerMinute = 0.5;
        $durationMinutes = $distanceKm > 0 ? $distanceKm / $avgSpeedKmPerMinute : 1;

        $promoCode = $data['promo_code'] ?? null;

        $cartype = CarType::with(['pricingRules' => fn ($q) => $q->where('is_active', true)])->get();
        
        $estimates = $cartype->map(function (CarType $cartype) use ($distanceKm, $durationMinutes, $promoCode, $riderId) {
             $now = now();
             $price = $cartype->priceAt($now);

        $fare = $price['base_fare']
            + ($distanceKm * $price['per_km'])
            + ($durationMinutes * $price['per_minute']);
        $fare = max($fare, $price['min_fare']);

            $estimate = [
                'car_type_id'      => $cartype->id,
                'type'             => $cartype->type,
                'icon'             => $cartype->icon,
                'capacity'         => $cartype->capacity,
                'distance_km'      => round($distanceKm, 2),
                'duration_minutes' => round($durationMinutes),
                'estimated_fare'   => round($fare, 2),
                'pricing_rule'     => $price['pricing_rule_name'],    

            ];

            // promo preview (only when a code and a rider are given)
            if ($promoCode && $riderId) {
                try {
                    $promo = $this->promos->validate($promoCode, $riderId, $estimate['estimated_fare']);

                    $estimate['promo_applied']       = true;
                    $estimate['discount']            = $promo['discount'];
                    $estimate['fare_after_discount'] = $promo['fare_after'];
                    $estimate['promo_message']       = null;
                } catch (PromoCodeException $e) {
                    $estimate['promo_applied']       = false;
                    $estimate['discount']            = 0;
                    $estimate['fare_after_discount'] = $estimate['estimated_fare'];
                    $estimate['promo_message']       = $e->getMessage();
                }
            }

            return $estimate;
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
        // ---- promo ----
        $promoCodeId = null;
        $discount    = 0.0;

        if (! empty($data['promo_code'])) {
            try {
                $promo = $this->promos->validate(
                    $data['promo_code'],
                    $riderId,
                    (float) $estimate['estimated_fare']
                );
            } catch (PromoCodeException $e) {
                throw ValidationException::withMessages(['promo_code' => $e->getMessage()]);
            }

            $promoCodeId = $promo['promo_code_id'];
            $discount    = $promo['discount'];
        }

        $amountToPay = (float) $estimate['estimated_fare'] - $discount;


        $wallet = $this->wallet->getBalance($riderId, 'rider');

        if ($data['payment_method'] === 'wallet') {                           // wallet balance check
            if ($wallet['balance'] < $amountToPay) {
                throw ValidationException::withMessages([
                    'method' => 'Insufficient wallet balance for this ride.',
                ]);
            }
        }


        return DB::transaction(function () use ($riderId, $data, $estimate, $promoCodeId) {
            $ride = Ride::create([
                'rider_id'       => $riderId,
                'car_type_id'    => $data['car_type_id'],
                'promo_code_id'  => $promoCodeId,
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
    return DB::transaction(function () use ($rideId, $driverId) {
        $ride = Ride::with('driver')
            ->where('id', $rideId)
            ->where('driver_id', $driverId)
            ->where('status', 'in_progress')
            ->lockForUpdate()
            ->firstOrFail();

        $fare      = (float) $ride->estimated_fare;
        $discount  = $this->promos->applyToRide($ride, $fare);
        $riderPays = round($fare - $discount, 2);

        $commissionRate = (float) $this->setting->get('commission_rate');
        $commission     = round($fare * $commissionRate, 2);
        $driverEarning  = round($fare - $commission, 2);

        $this->settlePayment($ride, $riderPays, $driverEarning);

        $ride->update([
            'status'            => 'completed',
            'final_fare'        => $riderPays,
            'commission_amount' => $commission,
            'driver_earning'    => $driverEarning,
            'ended_at'          => now(),
        ]);

        $this->log($ride->id, 'Ride_Completed', $driverId);

        return $ride;
    });
}

private function settlePayment(Ride $ride, float $riderPays, float $driverEarning): void
{
    $driverUserId = $ride->driver->user_id;
    $platformNet  = round($riderPays - $driverEarning, 2); // commission minus promo discount

    if ($ride->payment_method === 'wallet') {
        if ($riderPays > 0) {
            $this->wallet->debit($ride->rider_id, $riderPays, 'rider', "Payment for ride #{$ride->id}", $ride, 'ride_payment');
        }
        if ($driverEarning > 0) {
            $this->wallet->credit($driverUserId, $driverEarning, 'driver', "Earning for ride #{$ride->id}", $ride, 'ride_earning');
        }
    } else {
        // cash: the driver holds $riderPays, but is owed $driverEarning, so they settle the difference
        if ($platformNet > 0) {
            $this->wallet->debit($driverUserId, $platformNet, 'driver', "Commission for ride #{$ride->id}", $ride, 'commission', allowNegative: true);
        } elseif ($platformNet < 0) {
            $this->wallet->credit($driverUserId, abs($platformNet), 'driver', "Promo compensation for ride #{$ride->id}", $ride, 'promo_compensation');
        }
    }

    // platform side, the same in both payment methods
    if ($platformNet > 0) {
        $this->wallet->creditSystem($platformNet, "Commission for ride #{$ride->id}", $ride);
    } elseif ($platformNet < 0) {
        $this->wallet->debitSystem(abs($platformNet), "Promo discount for ride #{$ride->id}", $ride);
    }
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

    $this->wallet->debit($payerId, $fee, $payerType, "Cancellation fee for ride #{$ride->id}", $ride, 'cancellation_fee');

    if ($by === 'rider') {
        $this->wallet->credit($driver->user_id, $fee, 'driver', "Cancellation compensation for ride #{$ride->id}", $ride, 'cancellation_compensation');
    } else {
        $this->wallet->creditSystem($fee, "Driver cancellation fee for ride #{$ride->id}", $ride, 'cancellation_fee');
    }

    return $fee;
}
}
