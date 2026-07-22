<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'email' => 'nullable|email|unique:suppliers,email',
            'gst_number' => 'nullable|string|max:20',
            'address' => 'required|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}
