<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\WithdrawalRequest
 */
class WithdrawRequestResource extends JsonResource
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
            'amount'         => $this->amount,
            'bank_name'      => $this->bank_name,
            'account_number' => $this->account_number,
            'iban'           => $this->iban,
            'status'         => $this->status,
            'admin_note'     => $this->admin_note,
            'processed_at'   => $this->processed_at?->toISOString(),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),

            'driver' => $this->whenLoaded('driver', fn () => [
                'id'           => $this->driver->id,
                'name'         => $this->driver->user?->name,
                'phone_number' => $this->driver->user?->phone_number,
            ]),
        ];
    }
}