<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
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
            'car_type_id'    => $this->car_type_id,
            'license_number' => $this->license_number,
            'plate_number'   => $this->plate_number,
            'is_online'      => (bool) $this->is_online,
            'rate'           => $this->rate,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
        ];
    }
}
