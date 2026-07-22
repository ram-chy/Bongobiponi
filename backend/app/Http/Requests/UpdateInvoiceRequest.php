<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'billing_address' => 'sometimes|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string|in:draft,issued,partially_paid,paid,cancelled',
            'items' => 'sometimes|array|min:1',
            'items.*.delivery_challan_item_id' => 'required_with:items|exists:delivery_challan_items,id',
            'items.*.invoiced_quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.item_description' => 'nullable|string|max:500',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.delivery_challan_item_id.required_with' => 'Each item must reference a delivery challan item.',
            'items.*.delivery_challan_item_id.exists' => 'Selected delivery challan item does not exist.',
            'items.*.invoiced_quantity.required_with' => 'Each item must have an invoice quantity.',
            'items.*.invoiced_quantity.min' => 'Invoice quantity must be greater than zero.',
            'due_date.after_or_equal' => 'Due date must be on or after the invoice date.',
        ];
    }
}
