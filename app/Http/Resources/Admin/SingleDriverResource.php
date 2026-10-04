<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\DriverDocumentResource;
use App\Http\Resources\RideResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleDriverResource extends JsonResource
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
            'name'           => $this->user?->name,
            'email'          => $this->user?->email,
            'phone_number'   => $this->user?->phone_number,
            'avatar'         => $this->user?->avatar_url,

            'car_type_id'    => $this->car_type_id,
            'license_number' => $this->license_number,
            'plate_number'   => $this->plate_number,
            'is_online'      => (bool) $this->is_online,
            'rate'           => $this->rate,
            'status'         => $this->status,
            'current_location' => [
                'lat'     => $this->current_lat,
                'lng'     => $this->current_lng,
                'bearing' => $this->bearing,
            ],
            'created_at'     => $this->created_at,

            'earnings' => $this->whenCounted('rides', fn () => [
                'total_earnings' => (float) ($this->total_earnings ?? 0),
            ]),

            'rides_summary' => $this->whenCounted('rides', fn () => [
                'total'     => $this->rides_count,
                'completed' => $this->completed_rides_count,
                'cancelled' => $this->cancelled_rides_count,
            ]),

            'driver_documents' => DriverDocumentResource::collection($this->whenLoaded('driver_documents')),

            'rides' => $this->whenLoaded('rides', fn () => [
                'data' => RideResource::collection($this->rides->items()),
                'meta' => [
                    'current_page' => $this->rides->currentPage(),
                    'last_page'    => $this->rides->lastPage(),
                    'per_page'     => $this->rides->perPage(),
                    'total'        => $this->rides->total(),
                ],
            ]),
        ];
    }
    
}
