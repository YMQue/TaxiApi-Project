<?php

namespace App\Http\Controllers\Api\Rider;

use App\Events\RideRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRideRequest;
use App\Http\Requests\RequestRideRequest;
use App\Jobs\SearchForDriver;
use App\Models\ride_status_log;
use App\Models\Ride;
use App\Repositories\Contracts\RideRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RideController extends Controller 
{
    protected $rideRepository;
    public function __construct(RideRepositoryInterface $rideRepository)
    {$this->rideRepository = $rideRepository;}

    public function estimate(EstimateRideRequest $request)
    {
        $estimate = $this->rideRepository->estimateFare($request->validated());
        return response()->json([
            'success' => true,
            'message' => 'Fare estimates calculated successfully',
            'data' => $estimate,
            'errors' => null
        ],200);
    }

    public function request(RequestRideRequest $request)
{
        
        $estimates = $this->rideRepository->estimateFare($request->validated());

           $fare = collect($estimates)
                 ->firstWhere('car_type_id', $request->validated()['car_type_id'])['estimated_fare'];

    $ride = Ride::create([
        'rider_id' => auth()->id(),
        'car_type_id' => $request->car_type_id,
        'status' => 'searching',

        'pickup_lat' => $request->pickup_lat,
        'pickup_lng' => $request->pickup_lng,
        'pickup_address' => $request->pickup_address,

        'drop_lat' => $request->drop_lat,
        'drop_lng' => $request->drop_lng,
        'drop_address' => $request->drop_address,

        'estimated_fare' => $fare,
        'payment_method' => $request->payment_method,
    ]);

    ride_status_log::create([
        'ride_id' => $ride->id,
        'status' => 'searching',
        'changed_by' => 'system',
        'note' => 'Ride created',
    ]);

    SearchForDriver::dispatch($ride->id);

    return response()->json([
        'success' => true,
        'data' => $ride,
    ]);
}

}
