<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideDetailsResource extends JsonResource
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
        'payment_method' => $this->payment_method,

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

        'started_at'   => $this->started_at,
        'ended_at'     => $this->ended_at,
        'cancelled_at' => $this->cancelled_at,
        'cancel_reason'=> $this->cancel_reason,
        'cancel_by'    => $this->cancel_by,
        'created_at'   => $this->created_at,

        

        'route' => $this->whenLoaded('locations', fn () =>
            $this->locations->map(fn ($p) => [
                'lat'         => $p->lat,
                'lng'         => $p->lng,
                'recorded_at' => $p->recorded_at,
            ])
        ),

        'status_log' => $this->whenLoaded('statusLogs', fn () =>
            $this->statusLogs->map(fn ($l) => [
                'status'     => $l->status,
                'changed_by' => $l->changed_by,
                'changed_at' => $l->changed_at,
                'note'       => $l->note,
            ])
        ),
    ];
    }
}
