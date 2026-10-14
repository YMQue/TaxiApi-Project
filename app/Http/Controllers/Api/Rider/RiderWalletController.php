<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\TopupRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiderWalletController extends Controller
{
    use ApiResponse;

    public function __construct(private WalletService $walletService) {}

    public function wallet(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getBalance($request->user()->id, 'rider');

        return $this->success('Wallet balance', $wallet);
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->walletService->getTransactions($request->user()->id, 'rider');

        return $this->success('Wallet transactions', $transactions);
    }

    public function topup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:1', 'decimal:0,2'],
            'payment_ref' => ['required', 'string', 'max:255', 'unique:topup_requests,payment_ref'],
        ]);

        $topup = TopupRequest::create([
            'user_id'     => $request->user()->id,
            'amount'      => $data['amount'],
            'method'      => 'credit',
            'payment_ref' => $data['payment_ref'],
            'status'      => 'pending',
        ]);

        return $this->success('Waiting for Admin', $topup, 201);
    }
}