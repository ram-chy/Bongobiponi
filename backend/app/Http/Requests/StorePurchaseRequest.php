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
            'publisher_id' => 'required|exists:publishers,id',
            'invoice_no' => 'nullable|string|max:100',
            'invoice_date' => 'nullable|date',
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.book_id' => 'required|exists:books,id',
            'items.*.ordered_quantity' => 'nullable|integer|min:0',
            'items.*.received_quantity' => 'required|integer|min:1',
            'items.*.purchase_price' => 'required|numeric|min:0',
            'items.*.printed_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'required|numeric|min:0|max:100',
            'items.*.remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Supplier is required.',
            'supplier_id.exists' => 'Selected supplier does not exist.',
            'publisher_id.required' => 'Publisher is required.',
            'publisher_id.exists' => 'Selected publisher does not exist.',
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
            'items.*.printed_price.required' => 'Printed price is required for each item.',
            'items.*.printed_price.numeric' => 'Printed price must be a number.',
            'items.*.printed_price.min' => 'Printed price must not be negative.',
            'items.*.discount_percentage.required' => 'Purchase discount is required for each item.',
            'items.*.discount_percentage.numeric' => 'Purchase discount must be a number.',
            'items.*.discount_percentage.min' => 'Purchase discount must not be negative.',
            'items.*.discount_percentage.max' => 'Purchase discount must not exceed 100%.',
        ];
    }
}
