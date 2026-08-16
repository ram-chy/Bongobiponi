<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'order_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'pre_book' => 'nullable|boolean',
            'items' => 'sometimes|array|min:1',
            'items.*.book_id' => 'nullable|exists:books,id',
            'items.*.ordered_quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.unit' => 'required_with:items|string|max:50',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item is required.',
            'items.*.ordered_quantity.min' => 'Ordered quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price must not be negative.',
        ];
    }
}
