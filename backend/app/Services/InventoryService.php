<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryTransaction;
use App\Models\Stock;
use App\Repositories\InventoryRepository;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $repository,
        private readonly InventorySerialGeneratorService $serialGenerator,
        private readonly \App\Services\OrderStockReservationService $reservationService,
    ) {}

    public function increaseStock(
        int $bookId,
        int $quantity,
        InventoryTransactionType $type,
        ?string $referenceType,
        ?int $referenceId,
        string $transactionDate,
        ?string $remarks = null,
        ?int $createdBy = null,
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($bookId, $quantity, $type, $referenceType, $referenceId, $transactionDate, $remarks, $createdBy) {
            $stock = $this->repository->getOrCreateStock($bookId, lock: true);

            $newBalance = $stock->current_quantity + $quantity;

            $transaction = InventoryTransaction::create([
                'transaction_no' => $this->serialGenerator->generate(),
                'transaction_type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'book_id' => $bookId,
                'quantity_in' => $quantity,
                'quantity_out' => 0,
                'balance_after' => $newBalance,
                'transaction_date' => $transactionDate,
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]);

            $stock->update([
                'current_quantity' => $newBalance,
                'last_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function decreaseStock(
        int $bookId,
        int $quantity,
        InventoryTransactionType $type,
        ?string $referenceType,
        ?int $referenceId,
        string $transactionDate,
        ?string $remarks = null,
        ?int $createdBy = null,
        ?int $reservationOrderId = null,
    ): InventoryTransaction {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($bookId, $quantity, $type, $referenceType, $referenceId, $transactionDate, $remarks, $createdBy, $reservationOrderId) {
            $stock = $this->repository->getOrCreateStock($bookId, lock: true);

            $this->validateStock($stock, $quantity);

            $newBalance = $stock->current_quantity - $quantity;

            $transaction = InventoryTransaction::create([
                'transaction_no' => $this->serialGenerator->generate(),
                'transaction_type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'book_id' => $bookId,
                'quantity_in' => 0,
                'quantity_out' => $quantity,
                'balance_after' => $newBalance,
                'transaction_date' => $transactionDate,
                'remarks' => $remarks,
                'created_by' => $createdBy,
            ]);

            $stock->update([
                'current_quantity' => $newBalance,
                'last_transaction_id' => $transaction->id,
            ]);

            // Consume reservations only when physical stock leaves the warehouse
            // as a SALE (delivery). Stock-outs for damage/adjustments reduce
            // physical stock but keep customer commitments intact.
            if ($type === InventoryTransactionType::SALE) {
                $this->reservationService->consumeForBook(
                    $bookId,
                    $quantity,
                    $transaction->id,
                    $reservationOrderId,
                );
            }

            return $transaction;
        });
    }

    public function adjustStock(
        int $bookId,
        int $quantity,
        string $direction,
        string $transactionDate,
        ?string $remarks = null,
        ?int $createdBy = null,
    ): InventoryTransaction {
        return DB::transaction(function () use ($bookId, $quantity, $direction, $transactionDate, $remarks, $createdBy) {
            $stock = $this->repository->getOrCreateStock($bookId, lock: true);

            if ($direction === 'increase') {
                $newBalance = $stock->current_quantity + $quantity;
            } else {
                $this->validateStock($stock, $quantity);
                $newBalance = $stock->current_quantity - $quantity;
            }

            $transaction = InventoryTransaction::create([
                'transaction_no' => $this->serialGenerator->generate(),
                'transaction_type' => InventoryTransactionType::ADJUSTMENT,
                'reference_type' => null,
                'reference_id' => null,
                'book_id' => $bookId,
                'quantity_in' => $direction === 'increase' ? $quantity : 0,
                'quantity_out' => $direction === 'decrease' ? $quantity : 0,
                'balance_after' => $newBalance,
                'transaction_date' => $transactionDate,
                'remarks' => $remarks ?? "Stock adjustment ({$direction})",
                'created_by' => $createdBy,
            ]);

            $stock->update([
                'current_quantity' => $newBalance,
                'last_transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });
    }

    public function reverseTransaction(InventoryTransaction $transaction): InventoryTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $existingReversal = InventoryTransaction::where('reference_type', $transaction->reference_type)
                ->where('reference_id', $transaction->reference_id)
                ->where('remarks', 'like', 'Reversal of%')
                ->first();

            if ($existingReversal) {
                return $existingReversal;
            }

            $stock = $this->repository->getOrCreateStock($transaction->book_id, lock: true);

            if ($transaction->quantity_in > 0) {
                $this->validateStock($stock, $transaction->quantity_in);
                $newBalance = $stock->current_quantity - $transaction->quantity_in;
            } else {
                $newBalance = $stock->current_quantity + $transaction->quantity_out;
            }

            $reversal = InventoryTransaction::create([
                'transaction_no' => $this->serialGenerator->generate(),
                'transaction_type' => $transaction->transaction_type,
                'reference_type' => $transaction->reference_type,
                'reference_id' => $transaction->reference_id,
                'book_id' => $transaction->book_id,
                'quantity_in' => $transaction->quantity_out,
                'quantity_out' => $transaction->quantity_in,
                'balance_after' => $newBalance,
                'transaction_date' => now()->format('Y-m-d'),
                'remarks' => "Reversal of {$transaction->transaction_no}",
                'created_by' => auth()->id(),
            ]);

            $stock->update([
                'current_quantity' => $newBalance,
                'last_transaction_id' => $reversal->id,
            ]);

            if ($transaction->transaction_type === InventoryTransactionType::SALE) {
                $this->reservationService->restoreConsumedForTransaction(
                    $transaction->id,
                    $transaction->quantity_out,
                );
            }

            return $reversal;
        });
    }

    public function getCurrentStock(int $bookId): ?Stock
    {
        return $this->repository->getStock($bookId);
    }

    public function getCurrentStockForBooks(array $bookIds): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getStocksByBooks($bookIds);
    }

    public function recalculateStock(int $bookId): Stock
    {
        $stock = $this->repository->getOrCreateStock($bookId, lock: true);

        $transactions = InventoryTransaction::where('book_id', $bookId)
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        $balance = 0;

        DB::transaction(function () use ($transactions, $stock, &$balance) {
            foreach ($transactions as $transaction) {
                $balance += $transaction->quantity_in - $transaction->quantity_out;

                $transaction->update(['balance_after' => $balance]);
            }

            $stock->update([
                'current_quantity' => $balance,
                'last_transaction_id' => $transactions->last()?->id,
            ]);
        });

        return $stock->fresh();
    }

    public function validateStock(Stock $stock, int $quantity): void
    {
        $allowNegative = config('inventory.allow_negative_stock', false);

        if (! $allowNegative && $stock->current_quantity < $quantity) {
            throw new \RuntimeException(
                "Insufficient stock. Available: {$stock->current_quantity}, Requested: {$quantity}."
            );
        }
    }

    public function list(array $filters): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->repository->list($filters);
    }

    public function getLedger(int $bookId, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getLedger($bookId, $dateFrom, $dateTo);
    }
}
