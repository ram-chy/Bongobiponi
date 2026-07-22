<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiveOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'supplier_id' => $this->supplier_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'customer_id' => $this->customer_id,
            'expected_delivery_date' => $this->expected_delivery_date,
            'reference_no' => $this->reference_no,
            'notes' => $this->notes,
            'status' => $this->status,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => ReceiveOrderItemResource::collection($this->whenLoaded('items')),
            'total_amount' => $this->whenLoaded('items', function () {
                return $this->items->sum(function ($item) {
                    $base = $item->ordered_quantity * $item->purchase_price;
                    $discount = $base * ($item->discount_percentage / 100);
                    $tax = ($base - $discount) * ($item->tax_percentage / 100);
                    return $base - $discount + $tax;
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
