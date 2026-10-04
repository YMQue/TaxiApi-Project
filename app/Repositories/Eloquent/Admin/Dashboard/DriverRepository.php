<?php

namespace App\Repositories\Eloquent\Admin\Dashboard;

use App\Models\Driver;
use App\Repositories\Contracts\Admin\Dashboard\DriverRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;

class DriverRepository implements DriverRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $sortBy  = $filters['sort_by']  ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $perPage = $filters['per_page'] ?? 15;

        return Driver::query()
            ->with('user:id,name,email,phone_number')
            ->when(
                $filters['status'] ?? null,
                fn($q, $v) => $q->where('status', $v)
            )
            ->when(
                isset($filters['is_online']),
                fn($q) => $q->where('is_online', $filters['is_online'])
            )
            ->when(
                $filters['car_type_id'] ?? null,
                fn($q, $v) => $q->where('car_type_id', $v)
            )
            ->when(
                $filters['name'] ?? null,
                fn($q, $v) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$v}%"))
            )
            ->when(
                $filters['email'] ?? null,
                fn($q, $v) => $q->whereHas('user', fn($u) => $u->where('email', 'like', "%{$v}%"))
            )
            ->when(
                $filters['phone_number'] ?? null,
                fn($q, $v) => $q->whereHas('user', fn($u) => $u->where('phone_number', 'like', "%{$v}%"))
            )
            ->when($filters['search'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('plate_number', 'like', "%{$term}%")
                        ->orWhere('license_number', 'like', "%{$term}%")
                        ->orWhereHas('user', fn($u) => $u
                            ->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%")
                            ->orWhere('phone_number', 'like', "%{$term}%"));
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }


    public function find(int $id, array $filters = []): Driver
    {
        try {
            $driver = Driver::query()
                ->with([
                    'user:id,name,email,phone_number,avatar',
                    'driver_documents',
                ])
                ->withCount([
                    'rides',
                    'rides as completed_rides_count' => fn($q) => $q->where('status', 'completed'),
                    'rides as cancelled_rides_count' => fn($q) => $q->where('status', 'cancelled'),
                ])
                ->withSum(
                    ['rides as total_earnings' => fn($q) => $q->where('status', 'completed')],
                    'final_fare'
                )
                ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Driver not found',
                ], 404)
            );
        }

        $rides = $driver->rides()
            ->when(
                $filters['status'] ?? null,
                fn($q, $v) => $q->where('status', $v)
            )
            ->latest()
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        $driver->setRelation('rides', $rides);

        return $driver;
    }

    public function reject(int $id): Driver
{
    $driver = $this->find($id);

    $driver->update(['status' => 'rejected']);
    $driver->tokens()->delete(); // log the driver out of every device

    return $driver->refresh();
}

public function approve(int $id): Driver
{
    $driver = $this->find($id);

    $driver->update(['status' => 'approved']);

    return $driver->refresh();
}

public function suspend(int $id): Driver
{
    $driver = $this->find($id);

    $driver->update(['status' => 'pending']);

    return $driver->refresh();
}
}
