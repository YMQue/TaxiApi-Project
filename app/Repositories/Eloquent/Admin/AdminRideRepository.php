<?php 
namespace App\Repositories\Eloquent\Admin;

use App\Models\Ride;
use App\Repositories\Contracts\Admin\AdminRideRepositoryinterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminRideRepository implements AdminRideRepositoryinterface
{
    public function paginateForRides(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Ride::query()
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['rider_id'] ?? null, fn ($q, $v) => $q->where('rider_id', $v))
            ->when($filters['driver_id'] ?? null, fn ($q, $v) => $q->where('driver_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
  

public function findForRide(int $id): Ride
{
    return Ride::with([
        'statusLogs' => fn ($q) => $q->oldest(),
        'locations'  => fn ($q) => $q->oldest(),
    ])->findOrFail($id);
}

public function liveRides(): Collection
{
    return Ride::query()
        ->whereIn('status', ['accepted', 'arrived', 'in_progress'])
        ->with(['locations'])
        ->latest()
        ->get();
}
}