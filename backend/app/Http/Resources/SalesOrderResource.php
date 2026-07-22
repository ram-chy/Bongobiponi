<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_reference_uuid' => $this->document_reference_uuid,
            'sales_order_serial' => $this->sales_order_serial,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sales_order_source' => $this->sales_order_source,
            'sales_order_date' => $this->sales_order_date,
            'expected_delivery_date' => $this->expected_delivery_date,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'reference_notes' => $this->reference_notes,
            'notes' => $this->notes,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'approved_by' => $this->approver?->only(['id', 'first_name', 'last_name', 'email']),
            'approved_at' => $this->approved_at,
            'confirmed_at' => $this->confirmed_at,
            'pdf_generated_at' => $this->pdf_generated_at,
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
