<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\WithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;

class DriverWalletController extends Controller
{
        public function __construct(private WalletService $walletService) {}

        public function wallet(Request $request)
    {
        $driver_id = $request->user()->driver->id;
        $wallet = $this->walletService->getBalance($driver_id,'driver');
        return response()->json([
            'success' => true,
            'data' => $wallet
        ],201);
    }

     public function transactions(Request $request)
    {
        $transactions = $this->walletService->getTransactions($request->user()->driver->id,'driver');
        return response()->json([
            'success' => true,
            'data' => $transactions,
        ],201);
    }

    public function Withdraw(Request $request)
    {
        $data = $request->validate([
        'amount' => 'required|numeric|min:1',
        'bank_name' => 'required',
        'account_number' => 'required|numeric',
        'iban' => 'required'
        ]);
    $withdraw = WithdrawalRequest::create([
        'driver_id' => $request->user()->driver->id,
        'amount' => $data['amount'],
        'method' => 'debit',
        'bank_name' => $data['bank_name'],
        'account_number' => $data['account_number'],
        'iban' => $data['iban'],
        'status' => 'pending',
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Waiting for Admin',
        'data' => $withdraw
    ],201);
    }
        public function Withdraw_status(Request $request)
        {
            $driver_id = $request->user()->driver->id;
            $latest = WithdrawalRequest::where('driver_id', $driver_id)
                ->latest()
                ->first();
            return response()->json([
                'success' => true ,
                'message' => 'The latest Request: ',
                'data' => $latest,
            ],201);
        }


}
