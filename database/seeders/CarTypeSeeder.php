<?php

namespace Database\Seeders;

use App\Models\CarType;
use Illuminate\Database\Seeder;

class CarTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['type' => 'Economy', 'capacity' => 4, 'base_fare' => 5.00,  'per_km' => 1.50, 'per_minute' => 0.30, 'min_fare' => 10.00],
            ['type' => 'Comfort', 'capacity' => 4, 'base_fare' => 7.00,  'per_km' => 2.00, 'per_minute' => 0.40, 'min_fare' => 15.00],
            ['type' => 'XL',      'capacity' => 6, 'base_fare' => 10.00, 'per_km' => 2.50, 'per_minute' => 0.50, 'min_fare' => 20.00],
           // ['name' => 'Premium', 'capacity' => 4, 'base_fare' => 15.00, 'per_km' => 3.50, 'per_minute' => 0.70, 'min_fare' => 30.00],
        ];

        foreach ($types as $type) {
            CarType::firstOrCreate(
                ['type' => $type['type']],
                $type + ['icon' => null]
            );
        }
    }
}