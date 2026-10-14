<?php

namespace App\Repositories\Eloquent\Admin;

use App\Models\Setting;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SettingRepository implements SettingRepositoryInterface
{
    private const CACHE_KEY = 'settings.row';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () =>
            Setting::firstOrCreate([])->only((new Setting)->getFillable())
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function update(array $values): array
    {
        Setting::firstOrCreate([])->update($values);

        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}