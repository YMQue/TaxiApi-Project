<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListTopupRequestsRequest;
use App\Http\Requests\Admin\ListWithdrawRequestsRequest;
use App\Http\Requests\Admin\ManualWalletAdjustRequest;
use App\Http\Resources\Admin\TopupRequestResource;
use App\Http\Resources\Admin\WithdrawRequestResource;
use App\Repositories\Contracts\Admin\WalletRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        protected WalletRepositoryInterface $Wallet
    ) {}
     public function topup_index(ListTopupRequestsRequest $request)
    {
        $filters = $request->validated();

        $topups = $this->Wallet->Topup_paginateWithFilters(
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );

        return TopupRequestResource::collection($topups);
    }
    public function topup_approve( int $id , Request $request)
    {
        $data = $request->validate([
        'admin_note' => ['nullable', 'string', 'max:500'],
    ]);

    $topup = $this->Wallet->approveTopup($id, $data['admin_note'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Topup request retrieved successfully.',
            'data'    => new TopupRequestResource($topup),
        ],201);
    }

    public function topup_reject(Request $request, int $id)
{
    $data = $request->validate([
        'admin_note' => ['required', 'string', 'max:500'],
    ]);

    $topup = $this->Wallet->rejectTopup($id, $data['admin_note']);

    return response()->json([
            'success' => true,
            'message' => 'Topup request rejected.',
            'data'    => new TopupRequestResource($topup),
        ],201);
}
  public function Withdrawl_index(ListWithdrawRequestsRequest $request)
    {
        $filters = $request->validated();

        $Withdrawl = $this->Wallet->Withdrawl_paginateWithFilters(
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );

        return WithdrawRequestResource::collection($Withdrawl);
    }

    public function Withdrawl_approve( int $id , Request $request)
    {
        $data = $request->validate([
        'admin_note' => ['nullable', 'string', 'max:500'],
    ]);

    $Withdrawl = $this->Wallet->approveWithdrawl($id, $data['admin_note'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Topup request retrieved successfully.',
            'data'    => new WithdrawRequestResource($Withdrawl),
        ],201);
    }
        public function Withdrawl_reject(Request $request, int $id)
{
    $data = $request->validate([
        'admin_note' => ['required', 'string', 'max:500'],
    ]);

    $Withdrawl = $this->Wallet->rejectWithdrawl($id, $data['admin_note']);

    return response()->json([
            'success' => true,
            'message' => 'Topup request rejected.',
            'data'    => new WithdrawRequestResource($Withdrawl),
        ],201);
}
public function manualAdjust(ManualWalletAdjustRequest $request): JsonResponse
{
    $data = $request->validated();
 
    try {
        $this->Wallet->manualAdjust(
            (int) $data['user_id'],
            $data['user_type'],
            $data['type'],
            (float) $data['amount'],
            $data['reason'],
            $request->user()->id,
        );
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
 
    return response()->json([
        'success' => true,
        'message' => 'Wallet adjusted successfully.',
    ]);
}
}
