<?php

namespace App\Http\Controllers\Api\Rider;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Http\Request;

class RiderWalletController extends Controller
{
    public function __construct(protected WalletRepositoryInterface $wallets)
    {}
    
    public function show(Request $request)
    {
        return response()->json(
            $this->wallets->getRiderBalance($request->user())
        );
    }

    public function transactions(Request $request)
    {
        $validated = $request->validate([
            'type'=>'nullable|string',
            'per_page'=>'nullable|integer|min:1|max:100',
        ]);
        return WalletTransactionResource::collection(
            $this->wallets->riderTransactions(
                $request->user(),
                $validated['type'] ?? null,
                $validated['per_page'] ?? 20,
            )
        );
    }
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'method' => 'required|string',
            'payment_ref' => 'nullable|string',
        ]);
        $topup = $this->wallets->createTopupRequest($request->user(),$validated);

        return response()->json([
            'success' => 'true',
            'message' => 'your request has been sent , please wait for the admin response',
            'data' => $topup,
        ],201);
        
    }
}
