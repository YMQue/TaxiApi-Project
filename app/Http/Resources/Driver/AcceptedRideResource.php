<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcceptedRideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id'             => $this->id,
            'rider_id'       => $this->rider_id,
            'driver_id'      => $this->driver_id,
            'car_type_id'    => $this->car_type_id,
            'status'         => $this->status,
            'pickup_lat'     => $this->pickup_lat,
            'pickup_lng'     => $this->pickup_lng,
            'pickup_address' => $this->pickup_address,
            'drop_lat'       => $this->drop_lat,
            'drop_lng'       => $this->drop_lng,
            'drop_address'   => $this->drop_address,
            'estimated_fare' => $this->estimated_fare,
            'payment_method' => $this->payment_method,
        ];
    }
}
