<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $publisherId = $this->route('publisher') instanceof \App\Models\Publisher
            ? $this->route('publisher')->id
            : $this->route('publisher');

        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:publishers,email,' . $publisherId,
            'address' => 'sometimes|string',
            'remarks' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}
