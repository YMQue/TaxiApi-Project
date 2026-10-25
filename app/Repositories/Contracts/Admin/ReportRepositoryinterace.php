<?php

namespace App\Repositories\Contracts\Admin;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReportRepositoryinterace
{
        public function revenue(Carbon $from, Carbon $to, int $perPage): array;
        public function rides(Carbon $from, Carbon $to, int $perPage, ?string $status = null): array;
        public function drivers(Carbon $from, Carbon $to, int $perPage): LengthAwarePaginator;
        public function riders(Carbon $from, Carbon $to, int $perPage): LengthAwarePaginator;
        public function walletTransactions(int $userId, string $userType, int $perPage, Carbon $from, Carbon $to, ?string $category = null): array;





}