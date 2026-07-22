<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $supplierId = $this->route('supplier') instanceof \App\Models\Supplier
            ? $this->route('supplier')->id
            : $this->route('supplier');

        return [
            'name' => 'sometimes|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:suppliers,phone,' . $supplierId,
            'email' => 'nullable|email|unique:suppliers,email,' . $supplierId,
            'gst_number' => 'nullable|string|max:20',
            'address' => 'sometimes|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}
