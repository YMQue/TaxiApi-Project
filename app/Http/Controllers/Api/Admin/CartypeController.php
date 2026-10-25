<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarTypeRequest;
use App\Http\Requests\Admin\UpdateCarTypeRequest;
use App\Http\Resources\Admin\CarTypeResource;
use App\Models\CarType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CartypeController extends Controller
{
   public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->query('per_page', 10);
        $carTypes = CarType::latest()->paginate($perPage);

        return CarTypeResource::collection($carTypes);
    }

    /**
     * POST /api/admin/car-types
     * إضافة نوع سيارة جديد
     */
    public function store(StoreCarTypeRequest $request): JsonResponse
    {
        $carType = CarType::create($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Car type created successfully',
            'data'    => new CarTypeResource($carType)
        ], 201);
    }

    /**
     * PUT/PATCH /api/admin/car-types/{id}
     * تعديل أسعار أو بيانات نوع سيارة
     */
    public function update(UpdateCarTypeRequest $request, CarType $carType): JsonResponse
    {
        $carType->update($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Car type updated successfully',
            'data'    => new CarTypeResource($carType)
        ], 200);
    }

    /**
     * DELETE /api/admin/car-types/{id}
     * حذف نوع سيارة
     */
    public function destroy(CarType $carType): JsonResponse
    {
        $carType->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Car type deleted successfully'
        ], 200);
    }
}
