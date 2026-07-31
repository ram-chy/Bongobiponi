<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = Customer::query()->with('creator');

        if ($user = auth()->user()) {
            if (! $user->hasRole(['admin', 'manager'])) {
                $query->where('created_by', $user->id);
            }
        }

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function store(array $data): Customer
    {
        $data['customer_code'] = Customer::generateCustomerCode();

        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer->load('creator');
    }

    public function restore(int $id): Customer
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return $customer->load('creator');
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('customer_code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'customer_code', 'created_at', 'city', 'state'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
