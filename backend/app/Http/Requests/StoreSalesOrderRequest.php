<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'sales_order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:sales_order_date',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'reference_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.sales_order_quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one order item is required.',
            'items.min' => 'At least one order item is required.',
            'items.*.order_item_id.required' => 'Each item must reference an order item.',
            'items.*.order_item_id.exists' => 'Selected order item does not exist.',
            'items.*.sales_order_quantity.required' => 'Each item must have a sales order quantity.',
            'items.*.sales_order_quantity.min' => 'Sales order quantity must be greater than zero.',
        ];
    }
}
