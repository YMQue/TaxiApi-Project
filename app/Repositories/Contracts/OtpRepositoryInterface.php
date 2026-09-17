<?php 
namespace App\Repositories\Contracts;
use App\Models\OtpCode;

interface OtpRepositoryInterface
{
    public function create(array $data): OtpCode;
    public function getValidOtp(string $phone): ?OtpCode;
    public function markUsed(OtpCode $otp): bool;
    public function deleteUnusedForPhone(string $phone): int;
}
