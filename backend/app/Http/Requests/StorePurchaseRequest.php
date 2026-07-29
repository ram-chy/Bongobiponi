<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.ordered_quantity' => 'nullable|integer|min:0',
            'items.*.received_quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Selected supplier does not exist.',
            'purchase_date.required' => 'Purchase date is required.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.book_id.required' => 'Book is required for each item.',
            'items.*.book_id.exists' => 'Selected book does not exist.',
            'items.*.received_quantity.required' => 'Received quantity is required for each item.',
            'items.*.received_quantity.integer' => 'Received quantity must be a whole number.',
            'items.*.received_quantity.min' => 'Received quantity must be at least 1.',
            'items.*.purchase_price.required' => 'Purchase price is required for each item.',
            'items.*.purchase_price.numeric' => 'Purchase price must be a number.',
            'items.*.purchase_price.min' => 'Purchase price must not be negative.',
        ];
    }
}
