<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_no' => $this->expense_no,
            'expense_date' => $this->expense_date,
            'category' => new ExpenseCategoryResource($this->whenLoaded('category')),
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no,
            'amount' => $this->amount,
            'vendor_name' => $this->vendor_name,
            'remarks' => $this->remarks,
            'attachment' => $this->attachment
                ? route('expenses.download-attachment', $this->id)
                : null,
            'created_by' => $this->creator?->only(['id', 'first_name', 'last_name', 'email']),
            'updated_by' => $this->updater?->only(['id', 'first_name', 'last_name', 'email']),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
