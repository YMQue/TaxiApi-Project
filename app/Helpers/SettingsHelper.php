<?php

namespace App\Helpers;


use App\Models\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    public static function get($key, $default = null)
    {
        $settings = Cache::get('app_settings');
        if (is_null($settings)) {
            $settings = Settings::pluck('value', 'key')->toArray();
            Cache::forever('app_settings', $settings);
        }
        return $settings[$key] ?? $default;
    }

    public static function set($key, $value)
    {
        $updated = Settings::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
        if ($updated) {
            Cache::forget('app_settings');
        }
        return (bool) $updated;
    }
}