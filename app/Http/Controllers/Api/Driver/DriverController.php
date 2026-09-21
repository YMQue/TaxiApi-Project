<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\DriverArrived;
use App\Events\DriverLocationUpdated;
use App\Events\RideAccepted;
use App\Events\RideCompleted;
use App\Events\RideStarted;
use App\Http\Controllers\Controller;
use App\Jobs\SearchForDriver;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private RideRepositoryInterface $rides,
        private DriverRepositoryInterface $drivers,
    ) {}

    public function status(Request $request)
    {
        $driver = $request->user()->driver;

        return response()->json([
            'success' => true,
            'data' => [
                'is_online'   => $driver->is_online,
                'current_lat' => $driver->current_lat,
                'current_lng' => $driver->current_lng,
                'license_number' => $driver->license_number,
                'plate_number' =>$driver->plate_number, 
            ],
        ],200);
    }

    public function location(Request $request)
    {
        $data = $request->validate([
            'current_lat' => 'required|numeric|between:-90,90',
            'current_lng' => 'required|numeric|between:-180,180',
            'bearing'     => 'nullable|numeric|between:0,360',
        ]);

        $driver = $this->drivers->updateLocation(
            $request->user()->driver,
            $data['current_lat'],
            $data['current_lng'],
            $data['bearing'] ?? null,
        );

        event(new DriverLocationUpdated(
            $driver->current_lat,
            $driver->current_lng,
            $driver->bearing,
            $driver,
        ));

        return response()->json([
            'success' => true,
            'message' => 'Location updated',
            'data' => [
                'driver_id'   => $driver->id,
                'current_lat' => $driver->current_lat,
                'current_lng' => $driver->current_lng,
                'bearing'     => $driver->bearing,
            ],
        ],200);
    }

    public function accept(int $id, Request $request)
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->acceptRide($id, $driver->id);

        broadcast(new RideAccepted($ride));

        return response()->json([
            'success' => true,
            'message' => 'Ride accepted',
            'data' => [
                'id'              => $ride->id,
                'rider_id'        => $ride->rider_id,
                'driver_id'       => $ride->driver_id,
                'car_type_id'     => $ride->car_type_id,
                'status'          => $ride->status,
                'pickup_lat'      => $ride->pickup_lat,
                'pickup_lng'      => $ride->pickup_lng,
                'pickup_address'  => $ride->pickup_address,
                'drop_lat'        => $ride->drop_lat,
                'drop_lng'        => $ride->drop_lng,
                'drop_address'    => $ride->drop_address,
                'estimated_fare'  => $ride->estimated_fare,
                'payment_method'  => $ride->payment_method,
            ],
        ],200);
    }

    public function reject(int $id, Request $request)
    {
        $driver = $request->user()->driver;

        $this->rides->rejectRide($id, $driver->id);

        SearchForDriver::dispatch($id);

        return response()->json([
            'success' => true,
            'message' => 'Driver rejected. Searching again.',
        ],201);
    }

    public function arrived(int $id, Request $request)
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->markArrived($id, $driver->id);

        broadcast(new DriverArrived($ride, $driver));

        return response()->json([
            'success' => true,
            'message' => 'Arrived',
        ],200);
    }

    public function start(int $id, Request $request)
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->startRide($id, $driver->id);

         broadcast(new RideStarted($ride));


        return response()->json([
            'success' => true,
            'message' => 'Starting ride',
        ],200);
    }

    public function complete(int $id, Request $request)
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->completeRide($id, $driver->id);

        broadcast(new RideCompleted($ride));


        return response()->json([
            'success' => true,
            'message' => 'Ride Completed',
            'data'    => $ride,
        ],200);
    }
}