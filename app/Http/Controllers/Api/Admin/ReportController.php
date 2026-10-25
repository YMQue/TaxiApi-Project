<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use App\Repositories\Eloquent\Admin\ReportRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
     public function __construct(private ReportRepository $reports) {}

    public function revenue(Request $request)
{
    [$from, $to, $perPage] = $this->range($request);
    $r = $this->reports->revenue($from, $to, $perPage);

    return $this->respond($from, $to, $r['list'], $r['summary']);
}

public function rides(Request $request)
{
    [$from, $to, $perPage] = $this->range($request);
    $r = $this->reports->rides($from, $to, $perPage, $request->query('status'));

    return $this->respond($from, $to, $r['list'], $r['summary']);
}

public function drivers(Request $request)
{
    [$from, $to, $perPage] = $this->range($request);

    return $this->respond($from, $to, $this->reports->drivers($from, $to, $perPage));
}

public function riders(Request $request)
{
    [$from, $to, $perPage] = $this->range($request);

    return $this->respond($from, $to, $this->reports->riders($from, $to, $perPage));
}





public function driverTransactions(Request $request, int $driver)
{
    $driverModel = Driver::find($driver);

    if (! $driverModel) {
        return response()->json(['message' => 'No driver found.'], 404);
    }

    [$from, $to, $perPage] = $this->range($request);

    $r = $this->reports->walletTransactions(
        $driverModel->user_id, 'driver', $perPage, $from, $to, $request->query('category')
    );

    return $this->respond($from, $to, $r['list'], $r['wallet'], 'No transactions found.');
}

public function riderTransactions(Request $request, int $rider)
{
   $isRider = User::whereKey($rider)->exists()
        && ! Driver::where('user_id', $rider)->exists();

    if (! $isRider) {
        return response()->json(['message' => 'No rider found.'], 404);
    }
    [$from, $to, $perPage] = $this->range($request);

    $r = $this->reports->walletTransactions(
        $rider, 'rider', $perPage, $from, $to, $request->query('category')
    );

    return $this->respond($from, $to, $r['list'], $r['wallet'], 'No transactions found.');
}












private function respond(
    Carbon $from,
    Carbon $to,
    LengthAwarePaginator $list,
    ?array $summary = null,
    ?string $emptyMessage = null
) {
    return response()->json(
        [
            'from'    => $from,
            'to'      => $to,
            'message' => $list->total() === 0 ? $emptyMessage : null,
            'summary' => $summary,
        ] + $list->toArray()
    );
}







private function range(Request $request): array
{
    $data = $request->validate([
        'from'     => 'nullable|date',
        'to'       => 'nullable|date|after_or_equal:from',
        'per_page' => 'nullable|integer|min:1|max:100',
        'page'     => 'nullable|integer|min:1',
        'status'   => 'nullable|in:searching,accepted,arrived,in_progress,completed,cancelled',
    ]);

    return [
        Carbon::parse($data['from'] ?? now()->subDays(30))->startOfDay(),
        Carbon::parse($data['to'] ?? now())->endOfDay(),
        (int) ($data['per_page'] ?? 15),
    ];
}
}
