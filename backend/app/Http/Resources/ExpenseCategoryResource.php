<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
