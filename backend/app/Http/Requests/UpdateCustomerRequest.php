<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer') instanceof \App\Models\Customer
            ? $this->route('customer')->id
            : $this->route('customer');

        return [
            'name' => 'sometimes|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customerId,
            'phone' => 'sometimes|string|max:20|unique:customers,phone,' . $customerId,
            'alternate_phone' => 'nullable|string|max:20',
            'gst_number' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
            'billing_address' => 'sometimes|string',
            'shipping_address' => 'nullable|string',
            'city' => 'sometimes|string|max:255',
            'state' => 'sometimes|string|max:255',
            'country' => 'sometimes|string|max:255',
            'postal_code' => 'sometimes|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'opening_balance' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive',
            'notes' => 'nullable|string',
        ];
    }
}
