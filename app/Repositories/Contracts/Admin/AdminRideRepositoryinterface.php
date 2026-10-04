<?php
namespace App\Repositories\Contracts\Admin;

use App\Models\Ride;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AdminRideRepositoryinterface
{
    public function paginateForRides(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function findForRide(int $id): Ride;
    public function liveRides(): Collection;

}