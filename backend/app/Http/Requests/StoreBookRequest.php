<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'barcode' => 'nullable|string|max:20|unique:books,barcode',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'publisher_id' => 'nullable|exists:publishers,id',
            'category_id' => 'nullable|exists:categories,id',
            'edition' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'authors' => 'nullable|array',
            'authors.*' => 'exists:authors,id',
            'status' => 'nullable|boolean',
        ];
    }
}
