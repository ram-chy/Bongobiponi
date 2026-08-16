<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_serial' => $this->order_serial,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'order_source' => $this->order_source,
            'order_date' => $this->order_date,
            'expected_delivery_date' => $this->expected_delivery_date,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'pre_book' => (bool) $this->pre_book,
            'reference_notes' => $this->reference_notes,
            'notes' => $this->notes,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'approved_by' => $this->approver?->only(['id', 'first_name', 'last_name', 'email']),
            'approved_at' => $this->approved_at,
            'confirmed_at' => $this->confirmed_at,
            'pdf_generated_at' => $this->pdf_generated_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'comments' => OrderCommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
