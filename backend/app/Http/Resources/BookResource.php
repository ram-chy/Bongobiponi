<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'isbn' => $this->isbn,
            'barcode' => $this->barcode,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'publisher_id' => $this->publisher_id,
            'category_id' => $this->category_id,
            'edition' => $this->edition,
            'language' => $this->language,
            'purchase_price' => $this->purchase_price,
            'selling_price' => $this->selling_price,
            'minimum_stock' => $this->minimum_stock,
            'description' => $this->description,
            'cover_image' => $this->cover_image,
            'publisher' => $this->whenLoaded('publisher', fn () => [
                'id' => $this->publisher->id,
                'name' => $this->publisher->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'authors' => $this->whenLoaded('authors', fn () =>
                $this->authors->map(fn ($author) => [
                    'id' => $author->id,
                    'name' => $author->name,
                ])
            ),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'first_name' => $this->creator->first_name,
                'last_name' => $this->creator->last_name,
                'email' => $this->creator->email,
            ]),
            'updated_by' => $this->whenLoaded('updater', fn () => [
                'id' => $this->updater->id,
                'first_name' => $this->updater->first_name,
                'last_name' => $this->updater->last_name,
                'email' => $this->updater->email,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
