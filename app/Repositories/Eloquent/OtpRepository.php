<?php

namespace App\Repositories\Eloquent;

use App\Models\OtpCode;
use App\Repositories\Contracts\OtpRepositoryInterface;
use Carbon\Carbon;

class OtpRepository implements OtpRepositoryInterface
{
    public function create(array $data): OtpCode
    {
         return OtpCode::create($data);
    }

    public function getValidOtp(string $phone): ?OtpCode
    {
        return OtpCode::where('phone_number', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public function markUsed(OtpCode $otp): bool
    {
        return $otp->update(['used_at' => Carbon::now()]);
    }

    public function deleteUnusedForPhone(string $phone): int
    {
        return OtpCode::where('phone_number', $phone)->whereNull('used_at')->delete();
    }

}