<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromoRequest;
use App\Http\Requests\Admin\UpdatePromoRequest;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(Request $request)
    {
        $promos = PromoCode::query()
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%' . strtoupper($request->search) . '%'))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $promos]);
    }

    public function store(StorePromoRequest $request)
    {
        $promo = PromoCode::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'promo code created',
            'data'    => $promo,
        ], 201);
    }

    public function update(UpdatePromoRequest $request, PromoCode $promo)
    {
        $data = $request->validated();

        // لا نغيّر نوع الخصم أو قيمته بعد أول استخدام حتى تبقى السجلات متسقة
        if ($promo->usages()->exists()) {
            foreach (['type', 'value'] as $field) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $promo->{$field}) {
                    return response()->json([
                        'success' => false,
                        'message' => "cannot change {$field} of a used promo code, create a new one",
                    ], 422);
                }
            }
        }

        $promo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'promo code updated',
            'data'    => $promo->refresh(),
        ]);
    }

    public function destroy(PromoCode $promo)
    {
        // استُخدم من قبل: نعطله فقط، وإلا نحذفه (Soft Delete)
        if ($promo->usages()->exists()) {
            $promo->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'promo code has usages, it was deactivated instead of deleted',
            ]);
        }

        $promo->delete();

        return response()->json(['success' => true, 'message' => 'promo code deleted']);
    }

    public function usages(Request $request, PromoCode $promo)
    {
        $usages = $promo->usages()
            ->with('user:id,name')
            ->latest('used_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $usages]);
    }
}