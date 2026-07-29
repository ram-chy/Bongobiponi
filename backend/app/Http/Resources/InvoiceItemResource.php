<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deliveryChallan = $this->resource->relationLoaded('deliveryChallan') ? $this->resource->deliveryChallan : null;
        $orderBooking = $this->resource->relationLoaded('orderBooking') ? $this->resource->orderBooking : null;
        $quotation = $this->resource->relationLoaded('quotation') ? $this->resource->quotation : null;

        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'delivery_challan_id' => $this->delivery_challan_id,
            'delivery_challan_item_id' => $this->delivery_challan_item_id,
            'order_booking_id' => $this->order_booking_id,
            'order_booking_item_id' => $this->order_booking_item_id,
            'quotation_id' => $this->quotation_id,
            'quotation_item_id' => $this->quotation_item_id,
            'item_description' => $this->item_description,
            'unit' => $this->unit,
            'delivered_quantity' => (string) $this->delivered_quantity,
            'invoiced_quantity' => (string) $this->invoiced_quantity,
            'remaining_invoice_quantity' => (string) $this->remaining_invoice_quantity,
            'unit_price' => (string) $this->unit_price,
            'line_total' => (string) $this->line_total,
            'remarks' => $this->remarks,
            'delivery_challan_serial' => $deliveryChallan?->serial,
            'order_serial' => $orderBooking?->order_serial,
            'quotation_serial' => $quotation?->quotation_serial,
        ];
    }
}
