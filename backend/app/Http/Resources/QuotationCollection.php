<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class QuotationCollection extends ResourceCollection
{
    public $collects = QuotationResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
