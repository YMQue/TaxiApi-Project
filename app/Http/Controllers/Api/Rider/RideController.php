<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstimateRideRequest;
use App\Http\Requests\RequestRideRequest;
use App\Jobs\SearchForDriver;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function __construct(private RideRepositoryInterface $rides) {}

    public function estimate(EstimateRideRequest $request)
    {
        $estimate = $this->rides->estimateFare($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fare estimates calculated successfully',
            'data'    => $estimate,
            'errors'  => null,
        ]);
    }

    public function request(RequestRideRequest $request)
    {
        $ride = $this->rides->createRide($request->user()->id, $request->validated());

        SearchForDriver::dispatch($ride->id);

        return response()->json([
            'success' => true,
            'message' => 'Ride requested',
            'data'    => $ride,
        ], 201);
    }

    public function show(int $id, Request $request)
    {
        $ride = $this->rides->findForRider($id, $request->user()->id);

        abort_if(! $ride, 404, 'Ride not found');

        return response()->json([
            'success' => true,
            'message' => 'Your ride',
            'data'    => $ride,
        ]);
    }

    public function index(Request $request)
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

        return response()->json([
            'success' => true,
            'data'    => $rides->items(),
            'meta'    => [
                'current_page' => $rides->currentPage(),
                'last_page'    => $rides->lastPage(),
                'per_page'     => $rides->perPage(),
                'total'        => $rides->total(),
            ],
        ]);
    }
}