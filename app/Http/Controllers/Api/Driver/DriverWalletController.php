<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverWalletController extends Controller
{
    use ApiResponse;

    public function __construct(private WalletService $walletService) {}

    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getBalance($request->user()->driver->id, 'driver');

        return $this->success('Wallet balance', $wallet);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->walletService->getTransactions($request->user()->driver->id, 'driver');

        return $this->success('Wallet transactions', $transactions);
    }

    public function Withdraw(Request $request, SettingRepositoryInterface $settings): JsonResponse
    {
        $min = $settings->get('min_withdrawal_amount', 50);

        $data = $request->validate([
            'amount'         => ['required', 'numeric', "min:$min"],
            'bank_name'      => ['required', 'string'],
            'account_number' => ['required', 'numeric'],
            'iban'           => ['required', 'string'],
        ], [
            'amount.min' => "The minimum withdrawal amount is {$min}.",
        ]);

        $driverId = $request->user()->driver->id;

        if (WithdrawalRequest::where('driver_id', $driverId)->where('status', 'pending')->exists()) {
            throw new BusinessRuleException('You already have a pending withdrawal request.', 409);
        }

        $balance = (float) $this->walletService->getBalance($driverId, 'driver')['balance'];

        if ((float) $data['amount'] > $balance) {
            throw new BusinessRuleException('Insufficient wallet balance.');
        }

        $withdraw = WithdrawalRequest::create([
            'driver_id'      => $driverId,
            'amount'         => $data['amount'],
            'method'         => 'debit',
            'bank_name'      => $data['bank_name'],
            'account_number' => $data['account_number'],
            'iban'           => $data['iban'],
            'status'         => 'pending',
        ]);

        return $this->success('Waiting for Admin', $withdraw, 201);
    }

    public function Withdraw_status(Request $request): JsonResponse
    {
        $latest = WithdrawalRequest::where('driver_id', $request->user()->driver->id)
            ->latest()
            ->first();

        return $this->success('The latest request', $latest);
    }
}