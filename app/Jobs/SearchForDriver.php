<?php

namespace App\Jobs;

use App\Events\NoDriverFound;
use App\Events\RideRequest;
use App\Jobs\DriverTimeout;
use App\Models\Driver;
use App\Models\ride_status_log;
use App\Models\Ride;
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
    public function handle(): void
    {
        $ride = Ride::find($this->rideId);

        if (!$ride || $ride->status !== 'searching') {
            return;
        }
        $excludedIds = Cache::get("ride:{$ride->id}:excluded_drivers", []);

        $driver = Driver::where('is_online', true)
            ->where('status', 'approved')
            ->whereNotNull('current_lat')
            ->whereNotNull('current_lng')
            ->whereNotIn('id', $excludedIds)
            ->orderByRaw("
                ABS(current_lat - ?) + ABS(current_lng - ?)
            ", [
                $ride->pickup_lat,
                $ride->pickup_lng,
            ])
            ->first();

        if (!$driver) {
            $ride->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => 'No driver found',
                'cancel_by' => 'system',
            ]);
             Cache::forget("ride:{$ride->id}:excluded_drivers");


            broadcast(new NoDriverFound($ride));

            return;
        }

        ride_status_log::create([
            'ride_id' => $ride->id,
            'status' => 'driver_request_sent',
            'changed_by' => 'system',
            'note' => "driver_id:{$driver->id}",
        ]);

        broadcast(new RideRequest($ride, $driver));

      

        DriverTimeout::dispatch(
            $ride->id,
            $driver->id
        )->delay(now()->addSeconds(30));
    
    }
}
