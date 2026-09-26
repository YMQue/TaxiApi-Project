<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Models\TopupRequest;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;

class RiderWalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    public function wallet(Request $request)
    {
        $rider_id = $request->user()->id;
        $wallet = $this->walletService->getBalance($rider_id,'rider');
        return response()->json([
            'success' => true,
            'data' => $wallet
        ],201);
    }

    public function transactions(Request $request)
    {
        $transactions = $this->walletService->getTransactions($request->user()->id,'rider');
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ],201);
    }
    public function topup(Request $request)
    {
        $data = $request->validate([
        'amount' => 'required|numeric|min:1',
        'payment_ref' => 'required|string|max:255|unique:topup_requests,payment_ref',
        ]);
    $topup = TopupRequest::create([
        'user_id' => $request->user()->id,
        'amount' => $data['amount'],
        'method' => 'credit',
        'payment_ref' => $data['payment_ref'],
        'status' => 'pending',
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Waiting for Admin',
        'data' => $topup
    ],201);
    }

}
