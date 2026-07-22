<?php

namespace App\Http\Controllers;

use App\Enums\InventoryTransactionType;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Http\Requests\StoreDamageRequest;
use App\Http\Requests\StoreOpeningStockRequest;
use App\Http\Resources\InventoryTransactionCollection;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\StockResource;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $service,
    ) {}

    public function index(Request $request): InventoryTransactionCollection
    {
        $this->authorize('viewAny', InventoryTransaction::class);

        $transactions = $this->service->list($request->only([
            'search', 'book_id', 'transaction_type', 'reference_type',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new InventoryTransactionCollection($transactions);
    }

    public function show(int $bookId): JsonResponse
    {
        $this->authorize('viewAny', InventoryTransaction::class);

        $stock = $this->service->getCurrentStock($bookId);

        return $this->successResponse(
            new StockResource($stock),
            'Stock retrieved successfully.',
        );
    }

    public function ledger(Request $request, int $bookId): JsonResponse
    {
        $this->authorize('viewAny', InventoryTransaction::class);

        $transactions = $this->service->getLedger(
            $bookId,
            $request->input('date_from'),
            $request->input('date_to'),
        );

        return $this->successResponse(
            InventoryTransactionResource::collection($transactions),
            'Ledger retrieved successfully.',
        );
    }

    public function opening(StoreOpeningStockRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryTransaction::class);

        $transaction = $this->service->increaseStock(
            $request->validated('book_id'),
            $request->validated('quantity'),
            InventoryTransactionType::OPENING,
            null,
            null,
            $request->validated('transaction_date'),
            $request->validated('remarks') ?? 'Opening stock',
            auth()->id(),
        );

        return $this->successResponse(
            new InventoryTransactionResource($transaction),
            'Opening stock recorded successfully.',
            201,
        );
    }

    public function adjustment(StoreAdjustmentRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryTransaction::class);

        $transaction = $this->service->adjustStock(
            $request->validated('book_id'),
            $request->validated('quantity'),
            $request->validated('direction'),
            $request->validated('transaction_date'),
            $request->validated('remarks'),
            auth()->id(),
        );

        return $this->successResponse(
            new InventoryTransactionResource($transaction),
            'Stock adjustment recorded successfully.',
            201,
        );
    }

    public function damage(StoreDamageRequest $request): JsonResponse
    {
        $this->authorize('create', InventoryTransaction::class);

        $transaction = $this->service->decreaseStock(
            $request->validated('book_id'),
            $request->validated('quantity'),
            InventoryTransactionType::DAMAGE,
            null,
            null,
            $request->validated('transaction_date'),
            $request->validated('remarks') ?? 'Damaged stock',
            auth()->id(),
        );

        return $this->successResponse(
            new InventoryTransactionResource($transaction),
            'Damage recorded successfully.',
            201,
        );
    }
}
