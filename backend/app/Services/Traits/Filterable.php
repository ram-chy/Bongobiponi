<?php

namespace App\Services\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    abstract protected function getSearchColumns(): array;

    abstract protected function getSearchRelations(): array;

    abstract protected function getFilterMap(): array;

    abstract protected function getDateColumn(): ?string;

    abstract protected function getAllowedSorts(): array;

    abstract protected function getDefaultSort(): string;

    protected function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        $searchColumns = $this->getSearchColumns();
        $searchRelations = $this->getSearchRelations();

        return $query->where(function (Builder $q) use ($search, $searchColumns, $searchRelations) {
            foreach ($searchColumns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }

            foreach ($searchRelations as $relation => $columns) {
                $q->orWhereHas($relation, function (Builder $rq) use ($search, $columns) {
                    foreach ($columns as $column) {
                        $rq->orWhere($column, 'like', "%{$search}%");
                    }
                });
            }
        });
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($this->getFilterMap() as $filterKey => $column) {
            if (! empty($filters[$filterKey])) {
                $query->where($column, $filters[$filterKey]);
            }
        }

        if ($dateColumn = $this->getDateColumn()) {
            if (! empty($filters['date_from'])) {
                $query->whereDate($dateColumn, '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate($dateColumn, '<=', $filters['date_to']);
            }
        }

        return $query;
    }

    protected function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: $this->getDefaultSort();
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, $this->getAllowedSorts())) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
