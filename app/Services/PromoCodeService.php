<?php


namespace App\Services;

use App\Exceptions\PromoCodeException;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Ride;
use Illuminate\Support\Facades\DB;

class PromoCodeService
{
    /**
     * معاينة: تتحقق من الكوبون وترجع الخصم (لا تسجل شيئاً).
     *
     * @throws PromoCodeException
     */
    public function validate(string $code, int $userId, float $fare): array
    {
        $promo = PromoCode::where('code', strtoupper(trim($code)))->first();

        $this->assertUsable($promo, $userId, $fare);

        $discount = $this->calculateDiscount($promo, $fare);

        return [
            'promo_code_id' => $promo->id,
            'code'          => $promo->code,
            'discount'      => $discount,
            'fare_before'   => round($fare, 2),
            'fare_after'    => round($fare - $discount, 2),
        ];
    }

    /**
     * يحسب قيمة الخصم فقط.
     */
    public function calculateDiscount(PromoCode $promo, float $fare): float
    {
        $discount = $promo->type === 'percentage'
            ? $fare * ((float) $promo->value / 100)
            : (float) $promo->value;

        if ($promo->max_discount !== null) {
            $discount = min($discount, (float) $promo->max_discount);
        }

        $discount = min($discount, $fare); // الخصم لا يتجاوز سعر الرحلة

        return round($discount, 2);
    }

    /**
     * تُستدعى عند اكتمال الرحلة: تسجل الاستخدام وتزيد العداد وترجع الخصم.
     * ترجع 0 إذا لم يكن في الرحلة كوبون أو لم يعد الكوبون صالحاً.
     */
    public function applyToRide(Ride $ride, float $fare): float
    {
        if (! $ride->promo_code_id) {
            return 0.0;
        }

        return DB::transaction(function () use ($ride, $fare) {
            // لو سُجّل استخدام لهذه الرحلة قبل ذلك، لا نكرره
            $existing = PromoCodeUsage::where('ride_id', $ride->id)->first();
            if ($existing) {
                return (float) $existing->discount_amount;
            }

            // قفل صف الكوبون لمنع تجاوز usage_limit مع الطلبات المتزامنة
            $promo = PromoCode::withTrashed()
                ->lockForUpdate()
                ->find($ride->promo_code_id);

            if (! $promo) {
                return 0.0;
            }

            try {
                // الكوبون كان صالحاً وقت الطلب، فلا نفحص التاريخ والتفعيل الآن
                $this->assertUsable($promo, $ride->rider_id, $fare, checkWindow: false);
            } catch (PromoCodeException) {
                return 0.0;
            }

            $discount = $this->calculateDiscount($promo, $fare);

            PromoCodeUsage::create([
                'promo_code_id'   => $promo->id,
                'user_id'         => $ride->rider_id,
                'ride_id'         => $ride->id,
                'discount_amount' => $discount,
                'used_at'         => now(),
            ]);

            $promo->increment('used_count');

            return $discount;
        });
    }

    /**
     * يفحص كل الشروط بالترتيب ويرمي Exception عند أول شرط يفشل.
     *
     * @throws PromoCodeException
     */
    private function assertUsable(?PromoCode $promo, int $userId, float $fare, bool $checkWindow = true): void
    {
        if (! $promo) {
            throw new PromoCodeException('promo code not found');
        }

        if ($checkWindow) {
            if (! $promo->is_active) {
                throw new PromoCodeException('promo code is not active');
            }

            if ($promo->valid_from && now()->lt($promo->valid_from)) {
                throw new PromoCodeException('promo code is not valid yet');
            }

            if ($promo->valid_to && now()->gt($promo->valid_to)) {
                throw new PromoCodeException('promo code has expired');
            }
        }

        if ($promo->usage_limit !== null && $promo->used_count >= $promo->usage_limit) {
            throw new PromoCodeException('promo code usage limit reached');
        }

        $userUsages = PromoCodeUsage::where('promo_code_id', $promo->id)
            ->where('user_id', $userId)
            ->count();

        if ($userUsages >= $promo->user_limit_per_code) {
            throw new PromoCodeException('you have already used this promo code');
        }

        if ($fare < (float) $promo->min_ride_amount) {
            throw new PromoCodeException(
                'minimum ride amount for this promo code is ' . $promo->min_ride_amount
            );
        }
    }
}