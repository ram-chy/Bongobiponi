<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentCollection extends ResourceCollection
{
    public $collects = PaymentResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
