<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        'name'     => 'Admin',
        'phone'    => '01005538122',
        'password' => Hash::make('your-password'),
    ]
);
    }
}
