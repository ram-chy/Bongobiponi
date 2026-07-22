<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SalesOrderCollection extends ResourceCollection
{
    public $collects = SalesOrderResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
