<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarTypePricingRule extends Model
{
     protected $fillable = [
        'car_type_id', 'name', 'start_time', 'end_time',
        'base_fare', 'per_km', 'per_minute', 'min_fare', 'is_active',
    ];

    protected $casts = [
        'base_fare' => 'float', 'per_km' => 'float',
        'per_minute' => 'float', 'min_fare' => 'float',
        'is_active' => 'boolean',
    ];

    public function carType() { return $this->belongsTo(CarType::class); }

    public static function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));
        return $h * 60 + $m;
    }

    private static function intervals(int $s, int $e): array
    {
        return $s < $e ? [[$s, $e]] : [[$s, 1440], [0, $e]];   // overnight ranges split at midnight
    }

    public function covers(string $time): bool
    {
        $t = static::toMinutes($time);
        $s = static::toMinutes($this->start_time);
        $e = static::toMinutes($this->end_time);

        return $s < $e ? ($t >= $s && $t < $e) : ($t >= $s || $t < $e);
    }

    public static function overlaps(int $carTypeId, string $start, string $end, ?int $ignoreId = null): bool
    {
        $new = static::intervals(static::toMinutes($start), static::toMinutes($end));

        return static::where('car_type_id', $carTypeId)
            ->where('is_active', true)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->get()
            ->contains(function ($rule) use ($new) {
                $old = static::intervals(
                    static::toMinutes($rule->start_time),
                    static::toMinutes($rule->end_time)
                );
                foreach ($old as [$a, $b]) {
                    foreach ($new as [$c, $d]) {
                        if ($a < $d && $c < $b) return true;
                    }
                }
                return false;
            });
    }
}
