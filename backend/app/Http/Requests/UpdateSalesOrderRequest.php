<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'sales_order_date' => 'sometimes|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:sales_order_date',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string|in:draft,confirmed,approved,processing,ready_for_delivery,completed,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.order_item_id' => 'required_with:items|exists:order_items,id',
            'items.*.sales_order_quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item is required.',
            'items.*.order_item_id.required_with' => 'Each item must reference an order item.',
            'items.*.sales_order_quantity.required_with' => 'Each item must have a quantity.',
            'items.*.sales_order_quantity.min' => 'Quantity must be greater than zero.',
        ];
    }
}
