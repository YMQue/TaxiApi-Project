<?php
namespace App\Repositories\Eloquent\Admin;

use App\Exceptions\BusinessRuleException;
use App\Models\TopupRequest;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Notifications\DynamicPushNotification;
use App\Repositories\Contracts\Admin\WalletRepositoryInterface;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(protected WalletService $walletService ,  protected NotificationService $notifications)
{
}
    public function Topup_paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
       $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        return TopupRequest::query()
            ->with('user:id,name,phone_number')
            ->when($filters['status'] ?? null,
                fn ($q, $v) => $q->where('status', $v))
            ->when($filters['method'] ?? null,
                fn ($q, $v) => $q->where('method', $v))
            ->when($filters['user_id'] ?? null,
                fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['min_amount'] ?? null,
                fn ($q, $v) => $q->where('amount', '>=', $v))
            ->when($filters['max_amount'] ?? null,
                fn ($q, $v) => $q->where('amount', '<=', $v))
            ->when($filters['date_from'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('payment_ref', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($u) => $u
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('phone_number', 'like', "%{$term}%"));
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function approveTopup(int $id, ?string $note = null): TopupRequest
{
    $topup = DB::transaction(function () use ($id, $note) {
        $topup = TopupRequest::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($topup->status !== 'pending') {
            throw new BusinessRuleException('This top-up request has already been processed.', 409);
        }

        $this->walletService->credit($topup->user_id, (float) $topup->amount, 'rider', 'Wallet top-up', $topup);

        $topup->update(['status' => 'approved', 'admin_note' => $note]);

        return $topup->load('user');
    });

     $this->notifications->send(
        $topup->user,
        'payment',
        'Top-up approved',
        "Your top-up of {$topup->amount} has been added to your wallet.",
        ['topup_id' => $topup->id, 'status' => 'approved'],
    );

    return $topup;
}

public function rejectTopup(int $id, string $note): TopupRequest
{
      $topup = DB::transaction(function () use ($id, $note) {
        $topup = TopupRequest::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($topup->status !== 'pending') {
            throw new BusinessRuleException('This top-up request has already been processed.', 409);
        }

        $topup->update([
            'status'     => 'rejected',
            'admin_note' => $note,
        ]);

        return $topup->load('user');
    });
     $this->notifications->send(
        $topup->user,
        'payment',
        'Top-up rejected',
        "Your top-up request was rejected. Reason: {$note}",
        ['topup_id' => $topup->id, 'status' => 'rejected'],
    );

    return $topup;
}
public function Withdrawl_paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
{
    $sortBy  = $filters['sort_by']  ?? 'created_at';
    $sortDir = $filters['sort_dir'] ?? 'desc';
 
    return WithdrawalRequest::query()
        ->with([
            'driver:id,user_id',
            'driver.user:id,name,phone_number',
        ])
        ->when($filters['status'] ?? null,
            fn ($q, $v) => $q->where('status', $v))
        ->when($filters['bank_name'] ?? null,
            fn ($q, $v) => $q->where('bank_name', $v))
        ->when($filters['driver_id'] ?? null,
            fn ($q, $v) => $q->where('driver_id', $v))
        ->when($filters['min_amount'] ?? null,
            fn ($q, $v) => $q->where('amount', '>=', $v))
        ->when($filters['max_amount'] ?? null,
            fn ($q, $v) => $q->where('amount', '<=', $v))
        ->when($filters['date_from'] ?? null,
            fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
        ->when($filters['date_to'] ?? null,
            fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
        ->when($filters['search'] ?? null, function ($q, $term) {
            $q->where(function ($sub) use ($term) {
                $sub->where('bank_name', 'like', "%{$term}%")
                    ->orWhere('account_number', 'like', "%{$term}%")
                    ->orWhere('iban', 'like', "%{$term}%")
                    ->orWhereHas('driver.user', fn ($u) => $u
                        ->where('name', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%"));
            });
        })
        ->orderBy($sortBy, $sortDir)
        ->paginate($perPage)
        ->withQueryString();
}

public function approveWithdrawl(int $id, ?string $note = null): WithdrawalRequest
{
    $withdrawal = DB::transaction(function () use ($id, $note) {
        // lock the row so a double click / two admins can't credit twice
        $Withdrawl = WithdrawalRequest::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($Withdrawl->status !== 'pending') {
            throw new BusinessRuleException('This withdrawal request has already been processed.', 409);
        }

         $this->walletService->debit(
                    $Withdrawl->driver->user_id,
                    (float) $Withdrawl->amount,
                    'driver',
                    'Wallet Withdrawl',
                    $Withdrawl
                );
        

        $Withdrawl->update([
            'status'     => 'approved',
            'admin_note' => $note,
        ]);

        return $Withdrawl->load('driver.user');
    });

    $this->notifications->send(
        $withdrawal->driver->user,
        'payment',
        'Withdrawal approved',
        "Your withdrawal of {$withdrawal->amount} has been approved.",
        ['withdrawal_id' => $withdrawal->id, 'status' => 'approved'],
    );

    return $withdrawal;
}

public function rejectWithdrawl(int $id, string $note): WithdrawalRequest
{
    $Withdrawl = DB::transaction(function () use ($id, $note) {
        $Withdrawl = WithdrawalRequest::whereKey($id)->lockForUpdate()->firstOrFail();

        if ($Withdrawl->status !== 'pending') {
           throw new BusinessRuleException('This withdrawal request has already been processed.', 409);
        }

        $Withdrawl->update([
            'status'     => 'rejected',
            'admin_note' => $note,
        ]);

        return $Withdrawl->load('driver.user');
    });
     $this->notifications->send(
        $Withdrawl->driver->user,
        'payment',
        'Withdrawal rejected',
        "Your withdrawal request was rejected. Reason: {$note}",
        ['withdrawal_id' => $Withdrawl->id, 'status' => 'rejected'],
    );

    return $Withdrawl;

}

public function manualAdjust(
    int $userId,
    string $userType,
    string $type,
    float $amount,
    string $reason,
    int $adminId
): void {
    DB::transaction(function () use ($userId, $userType, $type, $amount, $reason, $adminId) {
        $description = "Manual adjustment by admin #{$adminId}: {$reason}";
 
        match ($type) {
    'credit' => $this->walletService->credit($userId,$amount,$userType,$description),
    'debit'  => $this->walletService->debit($userId,$amount,$userType,$description),
};
    });
}










}

    
