<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiveOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'expected_delivery_date' => 'sometimes|required|date|after_or_equal:today',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.book_id' => 'required_with:items|exists:books,id',
            'items.*.ordered_quantity' => 'required_with:items|integer|min:1',
            'items.*.purchase_price' => 'required_with:items|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Selected supplier does not exist.',
            'expected_delivery_date.required' => 'Expected delivery date is required.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be today or later.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.book_id.required_with' => 'Book is required for each item.',
            'items.*.book_id.exists' => 'Selected book does not exist.',
            'items.*.ordered_quantity.required_with' => 'Ordered quantity is required for each item.',
            'items.*.ordered_quantity.integer' => 'Ordered quantity must be a whole number.',
            'items.*.ordered_quantity.min' => 'Ordered quantity must be at least 1.',
            'items.*.purchase_price.required_with' => 'Purchase price is required for each item.',
            'items.*.purchase_price.numeric' => 'Purchase price must be a number.',
            'items.*.purchase_price.min' => 'Purchase price must not be negative.',
        ];
    }
}
