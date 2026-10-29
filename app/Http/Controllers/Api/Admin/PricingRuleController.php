<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePricingRuleRequest;
use App\Http\Requests\StorePricingRuleRequest;
use App\Models\CarTypePricingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PricingRuleController extends Controller
{
     public function index(Request $request): JsonResponse
    {
        $rules = CarTypePricingRule::with('carType:id,type')
            ->when($request->car_type_id, fn ($q, $id) => $q->where('car_type_id', $id))
            ->orderBy('car_type_id')->orderBy('start_time')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rules]);
    }

    public function store(StorePricingRuleRequest $request): JsonResponse
    {
        $rule = CarTypePricingRule::create($request->validated());
        return response()->json(['status' => 'success', 'message' => 'Pricing rule created', 'data' => $rule], 201);
    }

    public function update(UpdatePricingRuleRequest $request, CarTypePricingRule $pricingRule): JsonResponse
    {
        $pricingRule->update($request->validated());
        return response()->json(['status' => 'success', 'message' => 'Pricing rule updated', 'data' => $pricingRule]);
    }

    public function destroy(CarTypePricingRule $pricingRule): JsonResponse
    {
        $pricingRule->delete();
        return response()->json(['status' => 'success', 'message' => 'Pricing rule deleted']);
    }
}
