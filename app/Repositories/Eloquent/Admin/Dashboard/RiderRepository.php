<?php

namespace App\Repositories\Eloquent\Admin\Dashboard;

use App\Models\User;
use App\Repositories\Contracts\Admin\Dashboard\RiderRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;


class RiderRepository implements RiderRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return User::query()
            ->riders()
            ->when(
                $filters['status'] ?? null,
                fn($q, $v) => $q->where('status', $v)
            )
            ->when(
                $filters['name'] ?? null,
                fn($q, $v) => $q->where('name', 'like', "%{$v}%")
            )
            ->when(
                $filters['email'] ?? null,
                fn($q, $v) => $q->where('email', 'like', "%{$v}%")
            )
            ->when(
                $filters['phone_number'] ?? null,
                fn($q, $v) => $q->where('phone_number', 'like', "%{$v}%")
            )
            ->when(
                $filters['date_from'] ?? null,
                fn($q, $v) => $q->whereDate('created_at', '>=', $v)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn($q, $v) => $q->whereDate('created_at', '<=', $v)
            )
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone_number', 'like', "%{$term}%");
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    
   public function find(int $id, array $filters = []): User
{
    try {
        $rider = User::query()
            ->riders()
            ->with('wallet:id,user_id,balance,currency')
            ->withCount([
                'rides',
                'rides as completed_rides_count' => fn ($q) => $q->where('status', 'completed'),
                'rides as cancelled_rides_count' => fn ($q) => $q->where('status', 'cancelled'),
            ])
            ->findOrFail($id);
    } catch (ModelNotFoundException $e) {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Rider not found',
            ], 404)
        );
    }

    $rides = $rider->rides()
        ->when($filters['status'] ?? null,
            fn ($q, $v) => $q->where('status', $v))
        ->latest()
        ->paginate($filters['per_page'] ?? 15)
        ->withQueryString();

    $rider->setRelation('rides', $rides);

    return $rider;
}


    public function suspend(int $id): User
{
    $rider = $this->find($id);

    $rider->update(['status' => 'suspended']);
    $rider->tokens()->delete(); // log the rider out of every device

    return $rider->refresh();
}

public function activate(int $id): User
{
    $rider = $this->find($id);

    $rider->update(['status' => 'active']);

    return $rider->refresh();
}
}
