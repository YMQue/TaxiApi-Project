<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveRideResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id'     => $this->id,
            'rider_id'       => $this->rider_id,
            'driver_id'      => $this->driver_id,
            'car_type_id'    => $this->car_type_id,
            'status' => $this->status,

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

            'current_location' => $this->whenLoaded('locations', fn () =>
                $this->locations ? [
                    'lat'         => $this->locations->lat,
                    'lng'         => $this->locations->lng,
                    'recorded_at' => $this->locations->recorded_at,
                ] : null
            ),


            'started_at' => $this->started_at,
        ];
    }
}
