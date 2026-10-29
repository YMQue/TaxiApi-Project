<?php

namespace App\Jobs;

use App\Events\NoDriverFound;
use App\Events\RideRequest;
use App\Jobs\DriverTimeout;
use App\Models\Driver;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SearchForDriver implements ShouldQueue
{
    use Queueable;
    
    /**
     * Create a new job instance.
     */
    public function __construct(public int $rideId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SettingRepositoryInterface $settings): void
    {
        $ride = Ride::find($this->rideId);

        if (!$ride || $ride->status !== 'searching') {
            return;
        }
        $maxAttempts = (int) $settings->get('max_driver_search_attempts', 2);
        $timeout = (int) $settings->get('ride_request_timeout_sec', 30);
        $radius  = (float) $settings->get('driver_search_radius_km', 5);

        $excludedIds = Cache::get("ride:{$ride->id}:excluded_drivers", []);

         if (count($excludedIds) >= $maxAttempts) {           //الوصول للحد الأقصى
            $this->noDriverFound($ride);
            return;
        }

        $lat = $ride->pickup_lat;
        $lng = $ride->pickup_lng;

         $driver = Driver::query()
            ->select('drivers.*')
            ->selectRaw(
                '(6371 * acos(least(1, cos(radians(?)) * cos(radians(current_lat))
                  * cos(radians(current_lng) - radians(?))
                  + sin(radians(?)) * sin(radians(current_lat))))) AS distance_km',
                [$lat, $lng, $lat]
            )
            ->where('is_online', true)
            ->where('status', 'approved')
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereNotIn('id', $excludedIds)
            // استبعد السائقين المشغولين برحلة جارية
            ->whereNotExists(fn ($q) => $q->from('rides')
                ->whereColumn('rides.driver_id', 'drivers.id')
                ->whereIn('rides.status', ['accepted', 'arrived', 'in_progress']))
            ->having('distance_km', '<=', $radius)
            ->orderBy('distance_km')
            ->first();

       if (! $driver) {
            $this->noDriverFound($ride);
            return;
        }

        ride_status_log::create([
            'ride_id'    => $ride->id,
            'status'     => 'driver_request_sent',
            'changed_by' => 'system',
            'changed_at' => now(),
            'note'       => "driver_id:{$driver->id}",
        ]);

        broadcast(new RideRequest($ride, $driver));

        DriverTimeout::dispatch($ride->id, $driver->id)
            ->delay(now()->addSeconds($timeout));
    }

    private function noDriverFound(Ride $ride): void
    {
        $ride->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => 'No driver found',
            'cancel_by'     => 'system',
        ]);

        Cache::forget("ride:{$ride->id}:excluded_drivers");

        broadcast(new NoDriverFound($ride));
    }
}
