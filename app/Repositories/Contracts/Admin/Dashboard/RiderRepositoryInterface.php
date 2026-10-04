<?php
namespace App\Repositories\Contracts\Admin\Dashboard;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface RiderRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;
    public function find(int $id, array $filters = []): User;
    public function suspend(int $id): User;
    public function activate(int $id): User;

}