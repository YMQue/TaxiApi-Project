<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    Admin::updateOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name'        => 'Admin',
            'password'    => 'password', // hashed automatically by the cast
            'role'        => 'super',
            'permissions' => null,
        ]
    );
}
}
