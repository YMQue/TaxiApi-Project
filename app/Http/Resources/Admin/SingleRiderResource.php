<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\RideResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleRiderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone_number' => $this->phone_number,
            'status'       => $this->status,
            'avatar'       => $this->avatar_url,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            'wallet' => $this->whenLoaded('wallet', fn () => [
                'balance'  => $this->wallet?->balance,
                'currency' => $this->wallet?->currency,
            ]),

            'rides_summary' => $this->whenCounted('rides', fn () => [
                'total'     => $this->rides_count,
                'completed' => $this->completed_rides_count,
                'cancelled' => $this->cancelled_rides_count,
            ]),

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