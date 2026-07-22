<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryChallanItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $salesOrderItem = $this->resource->relationLoaded('salesOrderItem') ? $this->resource->salesOrderItem : null;
        $salesOrder = $this->resource->relationLoaded('salesOrder') ? $this->resource->salesOrder : null;
        $orderBooking = $this->resource->relationLoaded('orderBooking') ? $this->resource->orderBooking : null;
        $quotation = $this->resource->relationLoaded('quotation') ? $this->resource->quotation : null;

        $deliveredQuantity = (float) $this->delivered_quantity;
        $unitPrice = (float) $this->unit_price;
        $orderedQuantity = (float) $this->ordered_quantity;
        $discountPct = (float) ($salesOrderItem?->discount_percentage ?? 0);
        $taxPct = (float) ($salesOrderItem?->tax_percentage ?? 0);

        $remainingQty = $salesOrderItem ? (float) $salesOrderItem->remaining_sales_quantity : 0;
        $alreadyDelivered = max(0, $orderedQuantity - $remainingQty - $deliveredQuantity);
        $lineTotal = $deliveredQuantity * $unitPrice;
        $discountAmount = $lineTotal * $discountPct / 100;

        return [
            'id' => $this->id,
            'delivery_challan_id' => $this->delivery_challan_id,
            'sales_order_id' => $this->sales_order_id,
            'sales_order_item_id' => $this->sales_order_item_id,
            'order_id' => $this->order_booking_id,
            'order_item_id' => $this->order_booking_item_id,
            'quotation_id' => $this->quotation_id,
            'quotation_item_id' => $this->quotation_item_id,
            'source_type' => $salesOrderItem?->source_type ?? 'sales_order',
            'item_no' => $this->additional['item_no'] ?? 0,
            'description' => $this->item_description,
            'unit' => $this->unit,
            'ordered_quantity' => (string) $this->ordered_quantity,
            'already_delivered_quantity' => (string) $alreadyDelivered,
            'remaining_sales_quantity' => (string) $remainingQty,
            'delivery_quantity' => (string) $this->delivered_quantity,
            'unit_price' => (string) $this->unit_price,
            'discount_percentage' => (string) $discountPct,
            'discount_amount' => (string) round($discountAmount, 2),
            'tax_percentage' => (string) $taxPct,
            'tax_amount' => (string) round(($lineTotal - $discountAmount) * $taxPct / 100, 2),
            'line_total' => (string) round($lineTotal, 2),
            'remarks' => $this->remarks,
            'sort_order' => 0,
            'sales_order_serial' => $salesOrder?->sales_order_serial,
            'order_serial' => $orderBooking?->order_serial,
            'quotation_serial' => $quotation?->quotation_serial,
        ];
    }
}
