<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_serial' => $this->quotation_serial,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
'quotation_date' => $this->quotation_date?->format('Y-m-d'),
'valid_until' => $this->valid_until?->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => QuotationItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
