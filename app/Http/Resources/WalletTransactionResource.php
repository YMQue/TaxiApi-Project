<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $meta = $this->meta ?? [];
        
        return [
            'id' =>$this->id,
            'direction' =>$this->type,
            'operation_type' => $meta['reference_type'] ?? $this->fallbackOperationType(),
            'amount' => (float) $this->amount,
            'confirmed' => (bool) $this->confirmed,
            'description' => $meta['description'] ?? null,
            'reference_id' => $meta['reference_id'] ?? null,
            'created_at' => $this->created_at,
        ];
    }

    protected function fallbackOperationType(): string
    {
        return $this->type === 'deposit' ? 'topup' : 'withdrawal';
    }
}
