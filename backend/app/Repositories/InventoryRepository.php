<?php

namespace App\Repositories;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\Stock;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryRepository
{
    public function list(array $filters): LengthAwarePaginator
    {
        $query = InventoryTransaction::query()->with(['book', 'creator']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 15), 100));
    }

    public function getStock(int $bookId): ?Stock
    {
        return Stock::where('book_id', $bookId)->first();
    }

    public function getStocksByBooks(array $bookIds): \Illuminate\Database\Eloquent\Collection
    {
        if (empty($bookIds)) {
            return Stock::newModelInstance()->newCollection();
        }

        return Stock::whereIn('book_id', $bookIds)->get();
    }

    public function getOrCreateStock(int $bookId, bool $lock = false): Stock
    {
        $query = Stock::where('book_id', $bookId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $stock = $query->first();

        if ($stock) {
            return $stock;
        }

        return Stock::create(['book_id' => $bookId, 'current_quantity' => 0]);
    }

    public function getLedger(int $bookId, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = InventoryTransaction::where('book_id', $bookId)
            ->with('creator')
            ->orderBy('created_at', 'asc');

        if ($dateFrom) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }

        return $query->get();
    }

    public function findByTransactionNo(string $transactionNo): ?InventoryTransaction
    {
        return InventoryTransaction::where('transaction_no', $transactionNo)->first();
    }

    public function find(int $id): ?InventoryTransaction
    {
        return InventoryTransaction::find($id);
    }

    private function applySearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('transaction_no', 'like', "%{$search}%")
              ->orWhere('remarks', 'like', "%{$search}%")
              ->orWhereHas('book', function ($q) use ($search) {
                  $q->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
              });
        });
    }

    private function applyFilters($query, array $filters)
    {
        if (! empty($filters['book_id'])) {
            $query->where('book_id', $filters['book_id']);
        }

        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting($query, ?string $sort, ?string $direction)
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['transaction_no', 'transaction_date', 'created_at', 'transaction_type'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
