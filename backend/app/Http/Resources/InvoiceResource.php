<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial' => $this->serial,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'billing_address' => $this->billing_address,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'round_off' => $this->round_off,
            'grand_total' => $this->grand_total,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
