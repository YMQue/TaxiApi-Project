<?php

namespace App\Repositories\Contracts;

use App\Models\Admin;

interface AdminRepositoryinterface
{
    public function findByEmail(string $email): ?Admin;
}