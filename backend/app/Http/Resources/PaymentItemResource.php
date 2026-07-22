<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'paid_amount' => $this->paid_amount,
            'remarks' => $this->remarks,
        ];
    }
}
