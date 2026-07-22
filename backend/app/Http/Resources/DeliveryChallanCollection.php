<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class DeliveryChallanCollection extends ResourceCollection
{
    public $collects = DeliveryChallanResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
