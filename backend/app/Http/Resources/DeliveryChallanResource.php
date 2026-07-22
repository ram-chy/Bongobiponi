<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryChallanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_challan_serial' => $this->serial,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'delivery_date' => $this->delivery_date,
            'delivery_address' => $this->delivery_address,
            'transport_name' => $this->transport_name,
            'vehicle_number' => $this->vehicle_number,
            'driver_name' => $this->driver_name,
            'driver_mobile' => $this->driver_mobile,
            'lr_number' => $this->lr_number,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'status' => $this->status,
            'delivery_by' => $this->delivery_by,
            'receiver_name' => $this->receiver_name,
            'remarks' => $this->remarks,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'items' => DeliveryChallanItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
