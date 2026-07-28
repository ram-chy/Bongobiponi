<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class BookService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Book::query()->with(['creator', 'updater', 'publisher', 'category', 'authors']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Book
    {
        $authors = $data['authors'] ?? [];
        unset($data['authors']);

        $data['created_by'] = auth()->id();

        $book = Book::create($data);

        if (! empty($data['cover_image']) && str_contains($data['cover_image'], 'covers/temp/')) {
            $this->finalizeCoverImage($book);
        }

        if (! empty($authors)) {
            $book->authors()->sync($authors);
        }

        return $book->load(['publisher', 'category', 'authors']);
    }

    private function finalizeCoverImage(Book $book): void
    {
        $tempPath = $book->cover_image;
        if (!$tempPath) return;

        $titleWords = array_filter(explode(' ', mb_strtolower($book->title)));
        $prefix = implode('_', array_slice($titleWords, 0, 2));
        $timestamp = now()->format('Ymd_His');
        $ext = pathinfo($tempPath, PATHINFO_EXTENSION);
        $newName = "{$book->id}_{$prefix}_{$timestamp}.{$ext}";
        $newPath = "covers/{$newName}";

        $oldFullPath = Storage::disk('public')->path($tempPath);
        $newFullPath = Storage::disk('public')->path($newPath);

        $dir = dirname($newFullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($oldFullPath)) {
            rename($oldFullPath, $newFullPath);
            $book->update(['cover_image' => "covers/{$newName}"]);
        }
    }

    public function update(Book $book, array $data): Book
    {
        $authors = $data['authors'] ?? null;
        unset($data['authors']);

        $data['updated_by'] = auth()->id();

        $book->update($data);

        if ($authors !== null) {
            $book->authors()->sync($authors);
        }

        return $book->load(['creator', 'updater', 'publisher', 'category', 'authors']);
    }

    public function restore(int $id): Book
    {
        $book = Book::onlyTrashed()->findOrFail($id);
        $book->restore();

        return $book->load(['creator', 'updater', 'publisher', 'category', 'authors']);
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('isbn', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('subtitle', 'like', "%{$search}%")
              ->orWhereHas('publisher', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('category', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('authors', function (Builder $q) use ($search) {
                  $q->where('name', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['publisher_id'])) {
            $query->where('publisher_id', $filters['publisher_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['author_id'])) {
            $query->whereHas('authors', function (Builder $q) use ($filters) {
                $q->where('authors.id', $filters['author_id']);
            });
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['title', 'isbn', 'purchase_price', 'selling_price', 'created_at'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
