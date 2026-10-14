<?php

namespace App\Http\Controllers\Api\Rider;

use App\Events\RideCancelled;
use App\Events\RideRequest;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\RatingException;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRideRequest;
use App\Http\Requests\RequestRideRequest;
use App\Jobs\SearchForDriver;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RideController extends Controller
{
    use ApiResponse;

    public function __construct(private RideRepositoryInterface $rides) {}

    public function estimate(EstimateRideRequest $request): JsonResponse
    {
        $estimate = $this->rides->estimateFare($request->validated());

        return $this->success('Fare estimates calculated successfully', $estimate);
    }

    public function request(RequestRideRequest $request): JsonResponse
    {
        $ride = $this->rides->createRide($request->user()->id, $request->validated());

        SearchForDriver::dispatch($ride->id);

        return $this->success('Ride requested', $ride, 201);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $ride = $this->rides->findForRider($id, $request->user()->id);

        if (! $ride) {
            throw new BusinessRuleException('Ride not found', 404);
        }

        return $this->success('Your ride', $ride);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:50',
            'status'   => 'sometimes|string|in:searching,accepted,arrived,in_progress,completed,cancelled',
        ]);

        $rides = $this->rides->paginateForRider(
            $request->user()->id,
            $validated['per_page'] ?? 15,
            $validated['status'] ?? null,
        );

        return $this->success('Your rides', $rides->items(), 200, [
            'current_page' => $rides->currentPage(),
            'last_page'    => $rides->lastPage(),
            'per_page'     => $rides->perPage(),
            'total'        => $rides->total(),
        ]);
    }

    public function rate(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'rider_rating'  => ['required', 'integer', 'between:1,5'],
            'rider_comment' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $rating = $this->rides->rate(
                $id,
                'rider',
                $request->user()->id,
                $data['rider_rating'],
                $data['rider_comment'] ?? null
            );
        } catch (RatingException $e) {
            return $this->failure($e->getMessage(), null, $e->getCode() ?: 422);
        }

        return $this->success('Rating has been sent', [
            'id'            => $rating->id,
            'ride_id'       => $rating->ride_id,
            'rider_rating'  => $rating->rider_rating,
            'rider_comment' => $rating->rider_comment,
        ], 201);
    }

    public function cancel(int $id, Request $request): JsonResponse
{
    $data = $request->validate([
        'reason' => ['nullable', 'string', 'max:255'],
    ]);

    $ride = $this->rides->cancelRide($id, 'rider', $request->user()->id, $data['reason'] ?? null);

    broadcast(new RideCancelled($ride , 'rider',(float) $ride->cancellation_fee));

    return $this->success(
        $ride->cancellation_fee > 0
            ? "Ride cancelled. A fee of {$ride->cancellation_fee} was charged."
            : 'Ride cancelled',
        ['id' => $ride->id, 'status' => $ride->status, 'cancellation_fee' => $ride->cancellation_fee]
    );
}
}