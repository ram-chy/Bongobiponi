<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_no' => $this->purchase_no,
            'purchase_type' => $this->purchase_type,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'supplier_id' => $this->supplier_id,
            'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'status' => $this->status,
            'total_amount' => $this->whenLoaded('items', function () {
                return $this->items->sum('total');
            }),
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
