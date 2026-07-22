<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1',
            'direction' => 'required|in:increase,decrease',
            'transaction_date' => 'required|date',
            'remarks' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'Book is required.',
            'book_id.exists' => 'Selected book does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.integer' => 'Quantity must be a whole number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'direction.required' => 'Direction is required.',
            'direction.in' => 'Direction must be either increase or decrease.',
            'transaction_date.required' => 'Transaction date is required.',
        ];
    }
}
