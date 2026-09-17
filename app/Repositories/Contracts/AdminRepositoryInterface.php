<?php

namespace App\Contracts\Repositories;

use App\Models\Admin;

interface AdminRepositoryInterface
{
    public function findByEmail(string $email): ?Admin;
}