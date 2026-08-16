<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'pre_book' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'nullable|exists:books,id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.01',
            'items.*.description' => 'required|string|max:500',
            'items.*.unit' => 'required|string|max:50',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.ordered_quantity.required' => 'Each item must have an ordered quantity.',
            'items.*.ordered_quantity.min' => 'Ordered quantity must be greater than zero.',
            'items.*.unit_price.min' => 'Unit price must not be negative.',
        ];
    }
}
