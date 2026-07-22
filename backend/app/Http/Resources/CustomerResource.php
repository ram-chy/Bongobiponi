<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'gst_number' => $this->gst_number,
            'pan_number' => $this->pan_number,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'credit_limit' => $this->credit_limit,
            'opening_balance' => $this->opening_balance,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
