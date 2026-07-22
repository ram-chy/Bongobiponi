<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'sometimes|exists:customers,id',
            'quotation_date' => 'sometimes|date',
            'valid_until' => 'sometimes|date|after_or_equal:quotation_date',
            'status' => 'nullable|in:draft,sent,accepted,rejected,expired,converted',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string|max:500',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
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
            'items.*.description.required_with' => 'Each item must have a description.',
            'items.*.quantity.required_with' => 'Each item must have a quantity.',
            'items.*.quantity.min' => 'Item quantity must be greater than zero.',
            'items.*.unit.required_with' => 'Each item must have a unit.',
            'items.*.unit_price.required_with' => 'Each item must have a unit price.',
            'items.*.unit_price.min' => 'Item unit price must not be negative.',
        ];
    }
}
