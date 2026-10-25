<?php

namespace App\Http\Controllers\Api\Rider;

use App\Exceptions\PromoCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rider\ValidatePromoRequest;
use App\Services\PromoCodeService;

class PromoController extends Controller
{
    public function __construct(private PromoCodeService $service) {}

    public function validateCode(ValidatePromoRequest $request)
    {
        try {
            $result = $this->service->validate(
                $request->code,
                $request->user()->id,
                (float) $request->fare
            );
        } catch (PromoCodeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'promo code is valid',
            'data'    => $result,
        ]);
    }
}