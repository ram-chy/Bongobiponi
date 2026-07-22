<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryChallanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'delivery_date' => 'required|date',
            'delivery_address' => 'required|string',
            'transport_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_mobile' => 'nullable|string|max:20',
            'lr_number' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'delivery_by' => 'nullable|string|max:255',
            'receiver_name' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:draft,ready,dispatched,delivered,cancelled',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.delivered_quantity' => 'required|numeric|min:0.01',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one sales order item is required.',
            'items.min' => 'At least one sales order item is required.',
            'items.*.sales_order_item_id.required' => 'Each item must reference a sales order item.',
            'items.*.sales_order_item_id.exists' => 'Selected sales order item does not exist.',
            'items.*.delivered_quantity.required' => 'Each item must have a delivery quantity.',
            'items.*.delivered_quantity.min' => 'Delivery quantity must be greater than zero.',
        ];
    }
}
