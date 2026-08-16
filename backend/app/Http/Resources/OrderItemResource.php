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
            'source_type' => $this->source_type,
            'book_id' => $this->book_id,
            'item_no' => $this->item_no,
            'description' => $this->description,
            'unit' => $this->unit,
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
