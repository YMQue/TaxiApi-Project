<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListTopupRequestsRequest;
use App\Http\Requests\Admin\ListWithdrawRequestsRequest;
use App\Http\Requests\Admin\ManualWalletAdjustRequest;
use App\Http\Requests\ApproveRequest;
use App\Http\Requests\RejectRequest;
use App\Http\Resources\Admin\TopupRequestResource;
use App\Http\Resources\Admin\WithdrawRequestResource;
use App\Repositories\Contracts\Admin\WalletRepositoryInterface;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected WalletRepositoryInterface $wallet
    ) {}

    public function topupIndex(ListTopupRequestsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $topups = $this->wallet->Topup_paginateWithFilters(
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );

        return $this->success('Topup list', TopupRequestResource::collection($topups));
    }

    public function topupApprove(int $id, ApproveRequest $request): JsonResponse
    {
        $topup = $this->wallet->approveTopup($id, $request->validated()['admin_note'] ?? null);

        return $this->success("Topup request #{$id} approved successfully.", new TopupRequestResource($topup));
    }

    public function topupReject(int $id, RejectRequest $request): JsonResponse
    {
        $topup = $this->wallet->rejectTopup($id, $request->validated()['admin_note']);

        return $this->success("Topup request #{$id} rejected.", new TopupRequestResource($topup));
    }

    public function withdrawIndex(ListWithdrawRequestsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $withdrawals = $this->wallet->Withdrawl_paginateWithFilters(
            $filters,
            (int) ($filters['per_page'] ?? 15)
        );

        return $this->success('Withdrawal list', WithdrawRequestResource::collection($withdrawals));
    }

    public function withdrawApprove(int $id, ApproveRequest $request): JsonResponse
    {
        $withdrawal = $this->wallet->approveWithdrawl($id, $request->validated()['admin_note'] ?? null);

        return $this->success("Withdrawal request #{$id} approved successfully.", new WithdrawRequestResource($withdrawal));
    }

    public function withdrawReject(int $id, RejectRequest $request): JsonResponse
    {
        $withdrawal = $this->wallet->rejectWithdrawl($id, $request->validated()['admin_note']);

        return $this->success("Withdrawal request #{$id} rejected.", new WithdrawRequestResource($withdrawal));
    }

    public function manualAdjust(ManualWalletAdjustRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->wallet->manualAdjust(
            (int) $data['user_id'],
            $data['user_type'],
            $data['type'],
            (float) $data['amount'],
            $data['reason'],
            $request->user()->id,
        );

        return $this->success('Wallet adjusted successfully.', [], 201);
    }

    public function show(WalletService $wallets)
{
    return response()->json(
        $wallets->getBalance(auth('admin')->id(), 'admin')
    );
}
}