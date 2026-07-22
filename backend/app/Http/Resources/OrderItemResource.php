<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quotation_id' => $this->quotation_id,
            'quotation_item_id' => $this->quotation_item_id,
            'source_type' => $this->source_type,
            'item_no' => $this->item_no,
            'description' => $this->description,
            'unit' => $this->unit,
            'quoted_quantity' => $this->quoted_quantity,
            'ordered_quantity' => $this->ordered_quantity,
            'remaining_order_quantity' => $this->remaining_order_quantity,
            'unit_price' => $this->unit_price,
            'discount_percentage' => $this->discount_percentage,
            'discount_amount' => $this->discount_amount,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'line_total' => $this->line_total,
            'remarks' => $this->remarks,
            'sort_order' => $this->sort_order,
        ];
    }
}
