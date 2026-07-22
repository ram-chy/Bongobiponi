<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PublisherService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Publisher::query()->with(['creator', 'updater']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Publisher
    {
        return Publisher::create($data);
    }

    public function update(Publisher $publisher, array $data): Publisher
    {
        $data['updated_by'] = auth()->id();

        $publisher->update($data);

        return $publisher->load(['creator', 'updater']);
    }

    public function restore(int $id): Publisher
    {
        $publisher = Publisher::onlyTrashed()->findOrFail($id);
        $publisher->restore();

        return $publisher->load(['creator', 'updater']);
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', filter_var($filters['status'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'created_at', 'status'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
