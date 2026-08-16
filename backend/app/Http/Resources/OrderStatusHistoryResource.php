<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status?->value,
            'to_status' => $this->to_status->value,
            'changed_by' => $this->whenLoaded('changedBy', function () {
                return $this->changedBy
                    ? [
                        'id' => $this->changedBy->id,
                        'name' => trim(($this->changedBy->first_name ?? '') . ' ' . ($this->changedBy->last_name ?? '')),
                    ]
                    : null;
            }),
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
