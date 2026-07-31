<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseCategoryService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = ExpenseCategory::query()->with('creator');

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): ExpenseCategory
    {
        return ExpenseCategory::create($data);
    }

    public function update(ExpenseCategory $category, array $data): ExpenseCategory
    {
        $data['updated_by'] = auth()->id();
        $category->update($data);

        return $category->load('creator');
    }

    public function delete(ExpenseCategory $category): void
    {
        $category->delete();
    }

    public function findTrashed(int $id): ExpenseCategory
    {
        return ExpenseCategory::onlyTrashed()->findOrFail($id);
    }

    public function restore(ExpenseCategory $category): ExpenseCategory
    {
        $category->restore();

        return $category->load('creator');
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'is_active', 'created_at'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
