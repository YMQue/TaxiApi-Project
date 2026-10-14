<?php

namespace App\Repositories\Eloquent;

use App\Models\Driver;
use App\Models\Ride;
use App\Repositories\Contracts\Admin\SettingRepositoryInterface;
use App\Repositories\Contracts\DriverRepositoryInterface;

class DriverRepository implements DriverRepositoryInterface
{
    private SettingRepositoryInterface $setting;
    public function __construct(SettingRepositoryInterface $setting)
    {
        $this->setting = $setting;
    }
    public function create(array $data): Driver
    {
        return Driver::create($data);
    }

    public function findByUserId(int $userId): ?Driver
    {
        return Driver::where('user_id', $userId)->first();
    }
    public function findNearestAvailable(Ride $ride, array $excludedDriverIds = []): ?Driver
    {
        $distance = "(6371 * acos(
            cos(radians(?)) * cos(radians(current_lat)) *
            cos(radians(current_lng) - radians(?)) +
            sin(radians(?)) * sin(radians(current_lat))
        ))";

        return Driver::query()
            ->selectRaw("drivers.*, {$distance} as distance_km", [
                $ride->pickup_lat,
                $ride->pickup_lng,
                $ride->pickup_lat,
            ])
            ->where('is_online', true)
            ->where('status', 'active')
            ->whereNotIn('id', $excludedDriverIds)
            ->having('distance_km', '<=', $this->setting->get('driver_search_radius_km'))
            ->orderBy('distance_km')
            ->first();
    }
    public function updateLocation(Driver $driver, float $lat, float $lng, ?float $bearing = null): Driver
    {
        $driver->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'bearing'     => $bearing ?? 0,
        ]);
 
        return $driver;
    }
    
     
   
}
