<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $superAdmin = Admin::where('role', 'super')->firstOrFail(); // adjust to how you mark super admin

     Wallet::firstOrCreate(
        ['user_id' => $superAdmin->id, 'user_type' => 'admin'],
        ['balance' => 0, 'currency' => 'SAR']
    );
    }
}
