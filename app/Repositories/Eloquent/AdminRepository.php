<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\AdminRepositoryInterface;
use App\Models\Admin;

class AdminRepository implements AdminRepositoryInterface
{
    public function findByEmail(string $email): ?Admin
    {
        return Admin::where('email', $email)->first();
    }
}