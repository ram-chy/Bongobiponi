<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'biography' => $this->biography,
            'country' => $this->country,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
