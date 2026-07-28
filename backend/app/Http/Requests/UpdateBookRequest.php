<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('book') instanceof \App\Models\Book
            ? $this->route('book')->id
            : $this->route('book');

        return [
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $bookId,
            'barcode' => 'nullable|string|max:20|unique:books,barcode,' . $bookId,
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'publisher_id' => 'sometimes|nullable|exists:publishers,id',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'edition' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|string|max:500',
            'authors' => 'nullable|array',
            'authors.*' => 'exists:authors,id',
        ];
    }
}
