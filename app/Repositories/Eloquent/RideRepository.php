<?php
namespace App\Repositories\Eloquent;

use App\Models\CarType;
use App\Models\Driver;
use App\Models\Ride;
use App\Repositories\Contracts\RideRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class RideRepository implements RideRepositoryInterface
{
   public function estimateFare(array $data) 
   {
        $distanceKm = $this->calculateDistanceKm(
            $data['pickup_lat'],
            $data['pickup_lng'],
            $data['drop_lat'],
            $data['drop_lng'],
        );
          $avgSpeedKmPerMinute = 0.5;
          $durationMinutes = $distanceKm > 0 ? $distanceKm / $avgSpeedKmPerMinute : 1;

          $cartype = CarType::query()->get();
          $estimates = $cartype->map(function (CarType $cartype) use ($distanceKm,$durationMinutes) {
            $fare = $cartype->base_fare 
            +($distanceKm * $cartype->per_km)
            +($durationMinutes * $cartype->per_min);
            $fare = max($fare,$cartype->min_fare);
            return [ 
                'car_type_id'      => $cartype->id,
                'name'             => $cartype->name,
                'icon'             => $cartype->icon,
                'capacity'         => $cartype->capacity,
                'distance_km'      => round($distanceKm, 2),
                'duration_minutes' => round($durationMinutes),
                'estimated_fare'   => round($fare, 2),
            ];
          });
          return $estimates->toArray();
   }
     

    
    
        
   

     
    private function calculateDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}