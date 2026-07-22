<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $authorId = $this->route('author') instanceof \App\Models\Author
            ? $this->route('author')->id
            : $this->route('author');

        return [
            'name' => 'sometimes|string|max:255',
            'biography' => 'nullable|string',
            'country' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }
}
