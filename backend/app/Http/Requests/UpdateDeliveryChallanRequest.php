<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryChallanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_date' => 'sometimes|date',
            'delivery_address' => 'sometimes|string',
            'transport_name' => 'nullable|string|max:255',
            'vehicle_number' => 'nullable|string|max:50',
            'driver_name' => 'nullable|string|max:255',
            'driver_mobile' => 'nullable|string|max:20',
            'lr_number' => 'nullable|string|max:100',
            'currency' => 'nullable|string|max:10',
            'exchange_rate' => 'nullable|numeric|min:0',
            'delivery_by' => 'nullable|string|max:255',
            'receiver_name' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string|in:draft,ready,dispatched,delivered,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.order_booking_item_id' => 'nullable|exists:order_items,id',
            'items.*.delivered_quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.description' => 'required_without:items.*.order_booking_item_id|string|max:500',
            'items.*.unit' => 'required_without:items.*.order_booking_item_id|string|max:50',
            'items.*.unit_price' => 'required_without:items.*.order_booking_item_id|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item is required.',
            'items.*.delivered_quantity.required_with' => 'Each item must have a delivery quantity.',
            'items.*.delivered_quantity.min' => 'Delivery quantity must be greater than zero.',
            'items.*.description.required_without' => 'Description is required for manual items.',
            'items.*.unit.required_without' => 'Unit is required for manual items.',
            'items.*.unit_price.required_without' => 'Unit price is required for manual items.',
        ];
    }
}
