<?php

namespace App\Repositories\Eloquent\Admin;

use App\Models\Setting;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettingRepository implements SettingRepositoryInterface
{
    private const CACHE_KEY = 'settings.all';

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () =>
            Setting::pluck('value', 'key')->all()
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function update(array $values): array
    {
        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}