<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $settings = [
            [
                'key'         => 'commission_rate',
                'value'       => '0.20',
            ],
            [
                'key'         => 'driver_search_radius_km',
                'value'       => '5',
            ],
            [
                'key'         => 'ride_request_timeout_sec',
                'value'       => '30',
            ],
            [
                'key'         => 'cancellation_fee_amount',
                'value'       => '5.00',
            ],
            [
                'key'         => 'min_withdrawal_amount',
                'value'       => '50.00',
            ],
            [
                'key'         => 'otp_expiry_minutes',
                'value'       => '5',
            ],
            [
                'key'         => 'otp_max_attempts',
                'value'       => '3',
            ],
            [
                'key'         => 'currency',
                'value'       => 'SAR',
            ],
        ];

        foreach ($settings as $setting) {
            // استخدام updateOrCreate لتجنب التكرار عند تشغيل السيدر أكثر من مرة
            Settings::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

}
