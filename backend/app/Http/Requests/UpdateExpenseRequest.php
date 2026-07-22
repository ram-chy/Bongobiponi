<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_date' => 'sometimes|required|date',
            'category_id' => 'sometimes|required|exists:expense_categories,id',
            'payment_method' => 'sometimes|required|in:Cash,Bank Transfer,UPI,Cheque',
            'reference_no' => 'nullable|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'vendor_name' => 'sometimes|required|string|max:255',
            'remarks' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'expense_date.required' => 'Expense date is required.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'payment_method.required' => 'Payment method is required.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than zero.',
            'vendor_name.required' => 'Vendor name is required.',
            'attachment.mimes' => 'Attachment must be a JPG, PNG, or PDF file.',
            'attachment.max' => 'Attachment size must not exceed 5MB.',
        ];
    }
}
