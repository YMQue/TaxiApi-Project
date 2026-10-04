<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DriverIndexRequest;
use App\Http\Requests\Admin\RiderIndexRequest;
use App\Http\Resources\Admin\DriverResource;
use App\Http\Resources\Admin\RiderResource;
use App\Http\Resources\Admin\SingleDriverResource;
use App\Http\Resources\Admin\SingleRiderResource;
use App\Models\User;
use App\Repositories\Contracts\Admin\Dashboard\RiderRepositoryInterface;
use App\Repositories\Eloquent\Admin\Dashboard\DriverRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class Dashboard extends Controller
{
    public function __construct(private RiderRepositoryInterface $riders , private DriverRepository $drivers) {}
    public function rider_index(RiderIndexRequest $request)
    {
       
        return RiderResource::collection(
            $this->riders->paginateWithFilters($request->validated())
        );
    }
   public function rider_show(int $id, Request $request)
{
    $filters = $request->validate([
        'status'   => 'nullable|string|max:20',
        'per_page' => 'nullable|integer|min:1|max:100',
    ]);

    return new SingleRiderResource($this->riders->find($id, $filters));
}
   public function suspend(int $id)
{
    return response()->json([
        'success' => true,
        'message' => 'Rider suspended successfully',
        'data'    => new RiderResource($this->riders->suspend($id)),
    ]);
}

public function activate(int $id)
{
    return response()->json([
        'success' => true,
        'message' => 'Rider activated successfully',
        'data'    => new RiderResource($this->riders->activate($id)),
    ]);
}
//-----------------------------------------------------------------------------------------------------------------------------------------------------------//


public function driver_index(DriverIndexRequest $request)
{
    
    return DriverResource::collection(
        $this->drivers->paginateWithFilters($request->validated())
    );
}

public function driver_show(int $id, Request $request)
{
    $filters = $request->validate([
        'status'   => 'nullable|string|max:20',
        'per_page' => 'nullable|integer|min:1|max:100',
    ]);

    return new SingleDriverResource($this->drivers->find($id, $filters));
}

public function suspended(int $id)
{
    return response()->json([
        'success' => true,
        'message' => 'Driver suspended successfully',
        'data'    => new DriverResource($this->drivers->suspend($id)),
    ]);
}

public function approve(int $id)
{
    return response()->json([
        'success' => true,
        'message' => 'Rider approved successfully',
        'data'    => new DriverResource($this->drivers->approve($id)),
    ]);
}

public function reject(int $id)
{
    return response()->json([
        'success' => true,
        'message' => 'Driver rejected successfully',
        'data'    => new DriverResource($this->drivers->reject($id)),
    ]);
}


}
