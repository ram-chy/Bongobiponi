<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}
