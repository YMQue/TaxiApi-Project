<?php

namespace App\Jobs;

use App\Models\ride_status_log;
use App\Models\Ride;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class DriverTimeout implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $rideId  ,  public int $driverId)
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
        $excluded = Cache::get("ride:{$ride->id}:excluded_drivers", []);
        $excluded[] = $this->driverId;
        Cache::put("ride:{$ride->id}:excluded_drivers", $excluded, now()->addMinutes(2));

        ride_status_log::create([
            'ride_id' => $ride->id,
            'status' => 'driver_timeout',
            'changed_by' => 'system',
            'note' => "driver_id:{$this->driverId}",
        ]);

        SearchForDriver::dispatch($ride->id);
    }
    
}
