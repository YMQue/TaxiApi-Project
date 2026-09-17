<?php

namespace App\Repositories\Contracts;

use App\Models\Driver;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\WithdrawalRequest;

interface WalletRepositoryInterface
{
    //---------Rider-----------//
    public function getRiderBalance(User $user);
    public function riderTransactions(User $user,?string $type,int $perpage = 10);
    public function createTopupRequest(User $user,array $data);

    //---------Driver-----------//
    public function getDriverBalance(Driver $driver);
    public function driverTransactions(Driver $driver,?string $type ,int $perpage = 10);
    public function createDriverWithdrawalRequest(Driver $driver,array $data);
    public function latestWithdrawalStatus(Driver $driver);

    //---------Admin-----------//
    public function pendingTopupRequests(int $perPage);
 
    public function approveTopupRequest(TopupRequest $topup);
 
    public function rejectTopupRequest(TopupRequest $topup, string $admin_note);
 
    public function pendingWithdrawalRequests(int $perPage);
 
    public function approveWithdrawalRequest(WithdrawalRequest $withdrawal);
 
    public function rejectWithdrawalRequest(WithdrawalRequest $withdrawal, string $admin_note);

    public function manualAdjust(string $userType, int $userId, float $amount, string $reason);
}