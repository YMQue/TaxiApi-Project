<?php

namespace App\Http\Controllers\Api\Driver;

use App\Events\DriverArrived;
use App\Events\DriverLocationUpdated;
use App\Events\RideAccepted;
use App\Events\RideCancelled;
use App\Events\RideCompleted;
use App\Events\RideStarted;
use App\Exceptions\RatingException;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Driver\AcceptedRideResource;
use App\Jobs\SearchForDriver;
use App\Repositories\Contracts\DriverRepositoryInterface;
use App\Repositories\Contracts\RideRepositoryInterface;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    use ApiResponse;

    public function __construct(
        private RideRepositoryInterface $rides,
        private DriverRepositoryInterface $drivers,
        private WalletService $wallet
    ) {}

    public function status(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        return $this->success('Driver status', [
            'is_online'      => $driver->is_online,
            'current_lat'    => $driver->current_lat,
            'current_lng'    => $driver->current_lng,
            'license_number' => $driver->license_number,
            'plate_number'   => $driver->plate_number,
        ]);
    }

    public function location(Request $request): JsonResponse
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

        return $this->success('Location updated', [
            'driver_id'   => $driver->id,
            'current_lat' => $driver->current_lat,
            'current_lng' => $driver->current_lng,
            'bearing'     => $driver->bearing,
        ]);
    }

    public function accept(int $id, Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->acceptRide($id, $driver->id);

        broadcast(new RideAccepted($ride));

        return $this->success('Ride accepted', new AcceptedRideResource($ride));

    }

    public function reject(int $id, Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $this->rides->rejectRide($id, $driver->id);

        SearchForDriver::dispatch($id);

        return $this->success('Driver rejected. Searching again.');
    }

    public function arrived(int $id, Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->markArrived($id, $driver->id);

        broadcast(new DriverArrived($ride, $driver));

        return $this->success('Arrived');
    }

    public function start(int $id, Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->startRide($id, $driver->id);

        broadcast(new RideStarted($ride));

        return $this->success('Starting ride');
    }

    public function complete(int $id, Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        $ride = $this->rides->completeRide($id, $driver->id);

        broadcast(new RideCompleted($ride));

        return $this->success('Ride completed', $ride);
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'driver_rating'  => ['required', 'integer', 'between:1,5'],
            'driver_comment' => ['nullable', 'string', 'max:500'],
        ]);

        $driver = $request->user()->driver;

        if (! $driver) {
            return $this->failure('Driver profile not found', null, 403);
        }

        try {
            $rating = $this->rides->rate(
                $id,
                'driver',
                $driver->id,
                $data['driver_rating'],
                $data['driver_comment'] ?? null
            );
        } catch (RatingException $e) {
            return $this->failure($e->getMessage(), null, $e->getCode() ?: 422);
        }

        return $this->success('Rating has been sent', [
            'id'             => $rating->id,
            'ride_id'        => $rating->ride_id,
            'driver_rating'  => $rating->driver_rating,
            'driver_comment' => $rating->driver_comment,
        ], 201);
    }

    public function cancel(int $id, Request $request): JsonResponse
{
    $data = $request->validate([
        'reason' => ['nullable', 'string', 'max:255'],
    ]);

    $ride = $this->rides->cancelRide($id, 'driver', $request->user()->driver->id, $data['reason'] ?? null);

    SearchForDriver::dispatch($ride->id);
    broadcast(new RideCancelled($ride,'driver'));

    return $this->success(
        $ride->cancellation_fee > 0
            ? "Ride cancelled. A fee of {$ride->cancellation_fee} was charged."
            : 'Ride cancelled',
        ['id' => $ride->id, 'cancellation_fee' => $ride->cancellation_fee]
    );
}
}