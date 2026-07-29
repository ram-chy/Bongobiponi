<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryChallanItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $orderBooking = $this->resource->relationLoaded('orderBooking') ? $this->resource->orderBooking : null;
        $quotation = $this->resource->relationLoaded('quotation') ? $this->resource->quotation : null;

        $deliveredQuantity = (float) $this->delivered_quantity;
        $unitPrice = (float) $this->unit_price;
        $lineTotal = $deliveredQuantity * $unitPrice;

        return [
            'id' => $this->id,
            'delivery_challan_id' => $this->delivery_challan_id,
            'order_id' => $this->order_booking_id,
            'order_item_id' => $this->order_booking_item_id,
            'quotation_id' => $this->quotation_id,
            'quotation_item_id' => $this->quotation_item_id,
            'source_type' => 'order',
            'item_no' => $this->additional['item_no'] ?? 0,
            'description' => $this->item_description,
            'unit' => $this->unit,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'delivery_quantity' => (string) $this->delivered_quantity,
            'unit_price' => (string) $this->unit_price,
            'line_total' => (string) round($lineTotal, 2),
            'remarks' => $this->remarks,
            'sort_order' => 0,
            'order_serial' => $orderBooking?->order_serial,
            'quotation_serial' => $quotation?->quotation_serial,
        ];
    }
}
