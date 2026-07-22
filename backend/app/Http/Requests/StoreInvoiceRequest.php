<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'required|exists:customers,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'billing_address' => 'required|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|string|in:draft,issued,partially_paid,paid,cancelled',
            'items' => 'required|array|min:1',
            'items.*.delivery_challan_item_id' => 'required|exists:delivery_challan_items,id',
            'items.*.invoiced_quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.item_description' => 'nullable|string|max:500',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one delivery challan item is required.',
            'items.min' => 'At least one delivery challan item is required.',
            'items.*.delivery_challan_item_id.required' => 'Each item must reference a delivery challan item.',
            'items.*.delivery_challan_item_id.exists' => 'Selected delivery challan item does not exist.',
            'items.*.invoiced_quantity.required' => 'Each item must have an invoice quantity.',
            'items.*.invoiced_quantity.min' => 'Invoice quantity must be greater than zero.',
            'due_date.after_or_equal' => 'Due date must be on or after the invoice date.',
        ];
    }
}
