<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'driver_id'   => $this->driver_id,
            'rider_id'    => $this->rider_id,
            'car_type_id' => $this->car_type_id,

            'pickup' => [
                'lat'     => $this->pickup_lat,
                'lng'     => $this->pickup_lng,
                'address' => $this->pickup_address,
            ],
            'drop' => [
                'lat'     => $this->drop_lat,
                'lng'     => $this->drop_lng,
                'address' => $this->drop_address,
            ],

            'distance_km'    => $this->distance_km,
            'duration_min'   => $this->duration_min,
            'estimated_fare' => $this->estimated_fare,
            'final_fare'     => $this->final_fare,
            'payment_method' => $this->payment_method,

            'started_at'     => $this->started_at,
            'ended_at'       => $this->ended_at,
            'cancelled_at'   => $this->cancelled_at,
            'cancel_reason'  => $this->cancel_reason,
            'cancel_by'      => $this->cancel_by,

            'created_at'     => $this->created_at,
        ];
    }
}