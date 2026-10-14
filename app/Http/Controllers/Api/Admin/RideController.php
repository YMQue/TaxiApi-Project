<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListRidesRequest;
use App\Http\Resources\Admin\LiveRideResource;
use App\Http\Resources\Admin\RideDetailsResource;
use App\Http\Resources\RideResource;
use App\Repositories\Contracts\Admin\AdminRideRepositoryinterface;
use Illuminate\Http\JsonResponse;

class RideController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminRideRepositoryinterface $rides) {}

    public function index(ListRidesRequest $request): JsonResponse
    {
        $rides = $this->rides->paginateForRides(
            $request->validated(),
            $request->integer('per_page', 15)
        );

        return $this->success('Rides list', RideResource::collection($rides));
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(
            "Ride #{$id}",
            new RideDetailsResource($this->rides->findForRide($id))
        );
    }

    public function live(): JsonResponse
    {
        return $this->success(
            'Rides locations',
            LiveRideResource::collection($this->rides->liveRides())
        );
    }
}