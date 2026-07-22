<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PublisherCollection extends ResourceCollection
{
    public $collects = PublisherResource::class;

    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
