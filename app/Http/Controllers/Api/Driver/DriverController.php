<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\DriverArrived;
use App\Events\RideAccepted;
use App\Http\Controllers\Controller;
use App\Jobs\SearchForDriver;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DriverController extends Controller
{
    public function __construct(private RideRepositoryInterface $rides) {}

    public function status(Request $request)
    {

         $driver = $request->user()->driver;
        return response()->json([
            'success' => true,
            'data' => [
                'is_online'   => $driver->is_online,
                'current_lat' => $driver->current_lat,
                'current_lng' => $driver->current_lng,
            ],
              ],201);
    }
    public function accept(int $id)
{
    $driver = auth()->user()->driver;

    $ride = Ride::where('id', $id)
        ->where('status', 'searching')
        ->firstOrFail();

    $ride->update([
        'driver_id' => $driver->id,
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    ride_status_log::create([
        'ride_id' => $ride->id,
        'status' => 'accepted',
        'changed_by' => 'driver',
        'note' => "driver_id:{$driver->id}",
    ]);

    broadcast(new RideAccepted($ride));

    return response()->json([
        'success' => true,
        'message' => 'Ride accepted',
        'data' =>[ 
          'id' => $ride->id,
           'rider id'=> $ride->rider_id,
           'driver_id'=>  $ride->driver_id,
          'car_type_id' => $ride->car_type_id,
           'status'=> $ride->status,
           'pickup_lat'=> $ride->pickup_lat,
           'pickup_lng'=> $ride->pickup_lng,
           'pickup_address'=> $ride->pickup_address,
           'drop_lat'=> $ride->drop_lat,
          'drop_lng' => $ride->drop_lng,
         'drop_address' =>  $ride->drop_address,
         'estimated_fare' =>  $ride->estimated_fare,
          'payment_method'=>  $ride->payment_method,
        ]
    ],201);
}

public function reject(Request $request, int $id)
{
    $driver = $request->user()->driver;
    $ride = Ride::find($id);
    $excluded = Cache::get("ride:{$ride->id}:excluded_drivers", []);
    $excluded[] = $driver->id;
    Cache::put("ride:{$ride->id}:excluded_drivers", $excluded, now()->addMinutes(2));

    ride_status_log::create([
        'ride_id' => $id,
        'status' => 'driver_rejected',
        'changed_by' => 'driver',
        'note' => "driver_id:{$driver->id}",
    ]);

    SearchForDriver::dispatch($id);

    return response()->json([
        'success' => true,
        'message' => 'Driver rejected. Searching again.',
    ],201);
}    

public function arrived(int $id)
{
      $ride = Ride::where('id', $id)
        ->where('status', 'accepted')
        ->firstOrFail();  
        $driver = auth()->user()->driver;


          $ride->update([
        'driver_id' => $driver->id,
        'status' => 'arrived',
        'arrived' => now(),
    ]);

     ride_status_log::create([
        'ride_id' => $id,
        'status' => 'driver_arrived',
        'changed_by' => 'driver',
        'note' => "driver_id:{$driver->id}",
    ]);
            broadcast(new DriverArrived($ride, $driver));

        return response()->json([
            'status' => true,
            'message' =>'Arrived'
        ]);



}
}
