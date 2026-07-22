<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_no' => $this->transaction_no,
            'transaction_type' => $this->transaction_type->value,
            'transaction_type_label' => $this->transaction_type->label(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'book' => new BookResource($this->whenLoaded('book')),
            'book_id' => $this->book_id,
            'quantity_in' => $this->quantity_in,
            'quantity_out' => $this->quantity_out,
            'balance_after' => $this->balance_after,
            'transaction_date' => $this->transaction_date?->format('Y-m-d'),
            'remarks' => $this->remarks,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
