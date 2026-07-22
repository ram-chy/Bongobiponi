<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseSerialGeneratorService $serialGenerator,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Expense::query()->with(['category', 'creator']);

        $query = $this->applySearch($query, $filters['search'] ?? null);
        $query = $this->applyFilters($query, $filters);
        $query = $this->applySorting($query, $filters['sort'] ?? null, $filters['direction'] ?? null);

        return $query->paginate(min((int) ($filters['per_page'] ?? 10), 100));
    }

    public function store(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $data['expense_no'] = $this->serialGenerator->generate();

            if (isset($data['attachment']) && $data['attachment']) {
                $data['attachment'] = $data['attachment']->store('expenses', 'local');
            }

            $expense = Expense::create($data);

            $this->activityLogService->logCreate('expense', 'expense', $expense->id, $data);

            return $expense->load(['category', 'creator']);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            if (isset($data['attachment']) && $data['attachment']) {
                if ($expense->attachment) {
                    Storage::disk('local')->delete($expense->attachment);
                }
                $data['attachment'] = $data['attachment']->store('expenses', 'local');
            }

            $data['updated_by'] = auth()->id();
            $oldData = $expense->only(array_keys($data));
            $expense->update($data);
            $this->activityLogService->logUpdate('expense', 'expense', $expense->id, $oldData, $data);

            return $expense->load(['category', 'creator']);
        });
    }

    public function delete(Expense $expense): void
    {
        if ($expense->attachment) {
            Storage::disk('local')->delete($expense->attachment);
        }

        $expense->delete();
    }

    public function restore(int $id): Expense
    {
        $expense = Expense::onlyTrashed()->findOrFail($id);
        $expense->restore();

        $this->activityLogService->logRestore('expense', 'expense', $expense->id);

        return $expense->load(['category', 'creator']);
    }

    private function applySearch(Builder $query, ?string $search): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('expense_no', 'like', "%{$search}%")
              ->orWhere('vendor_name', 'like', "%{$search}%")
              ->orWhere('reference_no', 'like', "%{$search}%")
              ->orWhere('remarks', 'like', "%{$search}%");
        });
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    private function applySorting(Builder $query, ?string $sort, ?string $direction): Builder
    {
        $sort = $sort ?: 'created_at';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['expense_no', 'expense_date', 'created_at', 'amount', 'payment_method', 'vendor_name'];

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
