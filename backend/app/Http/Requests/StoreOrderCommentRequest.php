<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Comment is required.',
        ];
    }
}
