<?php

namespace App\Services;

use App\Repositories\Contracts\OtpRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    protected $otpRepository;
    protected $expiryMinutes = 5; // OTP expiration time in seconds (5 minutes)
    protected $maxAttempts = 3; // Maximum number of attempts allowed
    protected $blockMinutes = 15; // Block duration in minutes after exceeding max attempts

     public function __construct(OtpRepositoryInterface $otpRepository)
    {
        $this->otpRepository = $otpRepository;
    }

    public function generateOtp(string $phoneNumber)
    {
        $this->otpRepository->deleteUnusedForPhone($phoneNumber);
        $code=str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->otpRepository->create([
            'phone_number' => $phoneNumber,
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes($this->expiryMinutes)
        ]);
        Cache::forget('otp_attempts_' . $phoneNumber);
        Cache::forget('otp_blocked_' . $phoneNumber);
    }
    public function verifyOtp(string $phoneNumber, string $code)
    {
        if ($this->isPhoneBlocked($phoneNumber)) {
            return false;
        }
        
        $otp = $this->otpRepository->getValidOtp($phoneNumber);
        if(!$otp){
            $this->incrementAttempts($phoneNumber);
            return false;
        }
        if($otp->code!== $code){
            $this->incrementAttempts($phoneNumber);
            return false;
        }
        $this->otpRepository->markUsed($otp);
        Cache::forget('otp_attempts_' . $phoneNumber);
        Cache::forget('otp_blocked_' . $phoneNumber);
        return true;
    }
        // -----Methods for handling attempts and blocking-----
    protected function incrementAttempts(string $phoneNumber)
    {
       $key = 'otp_attempts_' . $phoneNumber;
       $attempts = Cache::increment($key);
       if($attempts >= $this->maxAttempts){ 
           Cache::put('otp_blocked_' . $phoneNumber, true, $this->blockMinutes * 60);
           Cache::forget($key);
       } else {
           Cache::put($key, $attempts, $this->blockMinutes * 60);
       }
    }

     public function isPhoneBlocked(string $phoneNumber): bool
    {
        return Cache::has('otp_blocked_' . $phoneNumber);
    }

     public function getRemainingAttempts(string $phoneNumber): int
    {
        if ($this->isPhoneBlocked($phoneNumber)) {
            return 0;
        }
        $attempts = Cache::get('otp_attempts_' . $phoneNumber, 0);
        return max(0, $this->maxAttempts - $attempts);
    }

}
