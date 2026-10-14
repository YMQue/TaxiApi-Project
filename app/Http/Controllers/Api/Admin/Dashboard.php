<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverIndexRequest;
use App\Http\Requests\Admin\RiderIndexRequest;
use App\Http\Resources\Admin\DriverResource;
use App\Http\Resources\Admin\RiderResource;
use App\Http\Resources\Admin\SingleDriverResource;
use App\Http\Resources\Admin\SingleRiderResource;
use App\Repositories\Contracts\Admin\Dashboard\RiderRepositoryInterface;
use App\Repositories\Eloquent\Admin\Dashboard\DriverRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Dashboard extends Controller
{
    use ApiResponse;

    public function __construct(
        private RiderRepositoryInterface $riders,
        private DriverRepository $drivers
    ) {}

    // ---------------------------- Riders ----------------------------

    public function rider_index(RiderIndexRequest $request): JsonResponse
    {
        return $this->success(
            'Riders list',
            RiderResource::collection($this->riders->paginateWithFilters($request->validated()))
        );
    }

    public function rider_show(int $id, Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status'   => 'nullable|string|max:20',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->success(
            "Rider #{$id}",
            new SingleRiderResource($this->riders->find($id, $filters))
        );
    }

    public function suspend(int $id): JsonResponse
    {
        return $this->success(
            'Rider suspended successfully',
            new RiderResource($this->riders->suspend($id))
        );
    }

    public function activate(int $id): JsonResponse
    {
        return $this->success(
            'Rider activated successfully',
            new RiderResource($this->riders->activate($id))
        );
    }

    // ---------------------------- Drivers ----------------------------

    public function driver_index(DriverIndexRequest $request): JsonResponse
    {
        return $this->success(
            'Drivers list',
            DriverResource::collection($this->drivers->paginateWithFilters($request->validated()))
        );
    }

    public function driver_show(int $id, Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status'   => 'nullable|string|max:20',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        return $this->success(
            "Driver #{$id}",
            new SingleDriverResource($this->drivers->find($id, $filters))
        );
    }

    public function suspended(int $id): JsonResponse
    {
        return $this->success(
            'Driver suspended successfully',
            new DriverResource($this->drivers->suspend($id))
        );
    }

    public function approve(int $id): JsonResponse
    {
        return $this->success(
            'Driver approved successfully',
            new DriverResource($this->drivers->approve($id))
        );
    }

    public function reject(int $id): JsonResponse
    {
        return $this->success(
            'Driver rejected successfully',
            new DriverResource($this->drivers->reject($id))
        );
    }
}