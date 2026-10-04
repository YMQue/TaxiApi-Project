<?php
namespace App\Repositories\Contracts\Admin\Dashboard;

use Illuminate\Pagination\LengthAwarePaginator;

interface DriverRepositoryInterface
{
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator;

}