<?php
namespace App\Repositories\Eloquent\Admin;

use App\Models\Admin;
use App\Models\Ride;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Repositories\Contracts\Admin\ReportRepositoryinterace;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReportRepository implements ReportRepositoryinterace
{
    public function revenue(Carbon $from, Carbon $to, int $perPage): array
{
    $completed = fn () => Ride::where('status', 'completed')->whereBetween('ended_at', [$from, $to]);

    $t = $completed()->selectRaw('
        COUNT(*) as rides_count,
        COALESCE(SUM(final_fare), 0) as collected,
        COALESCE(SUM(estimated_fare - final_fare), 0) as promo_discounts,
        COALESCE(SUM(commission_amount), 0) as commission,
        COALESCE(SUM(driver_earning), 0) as driver_earnings
    ')->first();

    $adminId       = Admin::where('role', 'super')->value('id');
    $adminWalletId = Wallet::where(['user_id' => $adminId, 'user_type' => 'admin'])->value('id');

    $cancellationFees = WalletTransaction::where('wallet_id', $adminWalletId)
        ->where('type', 'credit')
        ->where('category', 'cancellation_fee')
        ->whereBetween('created_at', [$from, $to])
        ->sum('amount');

    $daily = $completed()
        ->selectRaw('DATE(ended_at) as day, COUNT(*) as rides, SUM(final_fare) as collected, SUM(commission_amount) as commission')
        ->groupBy(DB::raw('DATE(ended_at)'))
        ->orderByDesc('day')
        ->paginate($perPage)
        ->through(fn ($r) => [
            'day'        => $r->day,
            'rides'      => (int) $r->rides,
            'collected'  => round($r->collected, 2),
            'commission' => round($r->commission, 2),
        ]);

    return [
        'summary' => [
            'rides_count'       => (int) $t->rides_count,
            'total_collected'   => round($t->collected, 2),
            'commission'        => round($t->commission, 2),
            'promo_discounts'   => round($t->promo_discounts, 2),
            'cancellation_fees' => round($cancellationFees, 2),
            'platform_net'      => round($t->commission - $t->promo_discounts + $cancellationFees, 2),
            'driver_earnings'   => round($t->driver_earnings, 2),
        ],
        'list' => $daily,
    ];
}

public function rides(Carbon $from, Carbon $to, int $perPage, ?string $status = null): array
{
    $base = Ride::whereBetween('created_at', [$from, $to]);

    $byStatus    = (clone $base)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
    $cancelledBy = (clone $base)->where('status', 'cancelled')
        ->selectRaw('cancel_by, COUNT(*) as count')->groupBy('cancel_by')->pluck('count', 'cancel_by');

    $total     = $byStatus->sum();
    $completed = $byStatus['completed'] ?? 0;

    $list = (clone $base)
        ->when($status, fn ($q) => $q->where('status', $status))
        ->with(['rider:id,name', 'driver.user:id,name'])
        ->latest('id')
        ->paginate($perPage)
        ->through(fn (Ride $r) => [
            'id'           => $r->id,
            'status'       => $r->status,
            'rider'        => $r->rider?->name,
            'driver'       => $r->driver?->user?->name,
            'fare'         => $r->final_fare ?? $r->estimated_fare,
            'commission'   => $r->commission_amount,
            'cancel_by' => $r->cancel_by,
            'created_at'   => $r->created_at,
        ]);

    return [
        'summary' => [
            'total'           => $total,
            'by_status'       => $byStatus,
            'completion_rate' => $total ? round($completed / $total * 100, 1) : 0,
            'cancel_by'    => $cancelledBy,
            'average_fare'    => round((clone $base)->where('status', 'completed')->avg('final_fare') ?? 0, 2),
        ],
        'list' => $list,
    ];
}

   public function drivers(Carbon $from, Carbon $to, int $perPage): LengthAwarePaginator
{
    $page = Ride::where('status', 'completed')
        ->whereNotNull('driver_id')
        ->whereBetween('ended_at', [$from, $to])
        ->selectRaw('driver_id, COUNT(*) as rides_count, SUM(driver_earning) as earnings, SUM(commission_amount) as commission_generated')
        ->groupBy('driver_id')
        ->orderByDesc('earnings')
        ->orderBy('driver_id')
        ->with('driver.user:id,name')
        ->paginate($perPage)
        ->through(fn ($r) => [
            'driver_id'            => $r->driver_id,
            'user_id'              => $r->driver?->user_id,
            'name'                 => $r->driver?->user?->name,
            'rating'               => $r->driver?->rate,
            'rides_count'          => (int) $r->rides_count,
            'earnings'             => round($r->earnings, 2),
            'commission_generated' => round($r->commission_generated, 2),
        ]);

    return $this->withWallets($page, 'driver');
}

public function riders(Carbon $from, Carbon $to, int $perPage): LengthAwarePaginator
{
    $page = Ride::where('status', 'completed')
        ->whereBetween('ended_at', [$from, $to])
        ->selectRaw('rider_id, COUNT(*) as rides_count, SUM(final_fare) as spent')
        ->groupBy('rider_id')
        ->orderByDesc('rides_count')
        ->orderByDesc('spent')
        ->orderBy('rider_id')
        ->with('rider:id,name')
        ->paginate($perPage)
        ->through(fn ($r) => [
            'rider_id'    => $r->rider_id,
            'user_id'     => $r->rider_id,
            'name'        => $r->rider?->name,
            'rides_count' => (int) $r->rides_count,
            'spent'       => round($r->spent, 2),
        ]);

    return $this->withWallets($page, 'rider');
}





public function walletTransactions(int $userId, string $userType, int $perPage, Carbon $from, Carbon $to, ?string $category = null): array
{
    $wallet = Wallet::where(['user_id' => $userId, 'user_type' => $userType])->first();

    if (! $wallet) {
        return [
            'wallet' => ['balance' => 0, 'currency' => 'SAR'],
            'list'   => new LengthAwarePaginator([], 0, $perPage),
        ];
    }

    $list = WalletTransaction::where('wallet_id', $wallet->id)
        ->whereBetween('created_at', [$from, $to])
        ->when($category, fn ($q) => $q->where('category', $category))
        ->latest('id')
        ->paginate($perPage)
        ->through(fn ($t) => [
            'id'            => $t->id,
            'type'          => $t->type,
            'category'      => $t->category,
            'amount'        => round($t->amount, 2),
            'balance_after' => round($t->balance_after, 2),
            'description'   => $t->description,
            'ride_id'       => $t->reference_type === 'ride' ? $t->reference_id : null,
            'created_at'    => $t->created_at,
        ]);

    return [
        'wallet' => ['balance' => round($wallet->balance, 2), 'currency' => $wallet->currency],
        'list'   => $list,
    ];
}





private function withWallets(LengthAwarePaginator $page, string $userType, int $recent = 5): LengthAwarePaginator
{
    $userIds = $page->getCollection()->pluck('user_id')->filter()->unique()->values();

    $wallets = Wallet::where('user_type', $userType)
        ->whereIn('user_id', $userIds)
        ->get()
        ->keyBy('user_id');

    // last N transactions per wallet in one query (needs MySQL 8 / MariaDB 10.2+)
    $ranked = DB::table('wallet_transactions')
        ->selectRaw('id, ROW_NUMBER() OVER (PARTITION BY wallet_id ORDER BY id DESC) as rn')
        ->whereIn('wallet_id', $wallets->pluck('id'));

    $transactions = WalletTransaction::joinSub($ranked, 't', 't.id', '=', 'wallet_transactions.id')
        ->where('t.rn', '<=', $recent)
        ->orderByDesc('wallet_transactions.id')
        ->get(['wallet_transactions.*'])
        ->groupBy('wallet_id');

    return $page->through(function (array $row) use ($wallets, $transactions) {
        $wallet = $wallets->get($row['user_id']);

        $row['wallet'] = [
            'balance'             => $wallet ? round($wallet->balance, 2) : 0,
            'currency'            => $wallet->currency ?? 'SAR',
            'recent_transactions' => ($wallet ? $transactions->get($wallet->id, collect()) : collect())
                ->map(fn ($t) => [
                    'id'            => $t->id,
                    'type'          => $t->type,
                    'category'      => $t->category,
                    'amount'        => round($t->amount, 2),
                    'balance_after' => round($t->balance_after, 2),
                    'description'   => $t->description,
                    'created_at'    => $t->created_at,
                ])->values(),
        ];

        return $row;
    });
}
}