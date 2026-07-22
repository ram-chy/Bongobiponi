<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

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

        if (! empty($authors)) {
            $book->authors()->sync($authors);
        }

        return $book->load(['publisher', 'category', 'authors']);
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
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
        }

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

        $allowedSorts = ['title', 'isbn', 'purchase_price', 'selling_price', 'created_at', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
