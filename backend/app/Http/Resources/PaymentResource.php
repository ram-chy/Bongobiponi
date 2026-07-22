<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_no' => $this->payment_no,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'payment_date' => $this->payment_date,
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no,
            'remarks' => $this->remarks,
            'total_amount' => $this->total_amount,
            'payment_status' => $this->payment_status,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => PaymentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
