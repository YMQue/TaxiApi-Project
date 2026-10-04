<?php
namespace App\Repositories\Contracts\Admin;

use App\Models\TopupRequest;
use App\Models\WithdrawalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    public function Topup_paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function approveTopup(int $id, ?string $note = null): TopupRequest;
    public function rejectTopup(int $id, string $note): TopupRequest;
    public function Withdrawl_paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function approveWithdrawl(int $id, ?string $note = null): WithdrawalRequest;
    public function rejectWithdrawl(int $id, string $note): WithdrawalRequest;
    public function manualAdjust(
    int $userId,
    string $userType,
    string $type,
    float $amount,
    string $reason,
    int $adminId
): void;


}