<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopupRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            return [
            'id'          => $this->id,
            'amount'      => $this->amount,
            'method'      => $this->method,
            'payment_ref' => $this->payment_ref,
            'status'      => $this->status,
            'admin_note'  => $this->admin_note,
            'user'        => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'phone_number' => $this->user->phone_number,
            ]),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
