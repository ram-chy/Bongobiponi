<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseCollection;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\ActivityLogService;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): ExpenseCollection
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = $this->expenseService->list($request->only([
            'search', 'category_id', 'payment_method',
            'date_from', 'date_to', 'sort', 'direction', 'per_page',
        ]));

        return new ExpenseCollection($expenses);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $this->authorize('create', Expense::class);

        $expense = $this->expenseService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new ExpenseResource($expense), 'Expense created successfully', 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorize('view', $expense);

        $expense->load(['category', 'creator', 'updater']);

        return $this->successResponse(new ExpenseResource($expense), 'Expense retrieved successfully');
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        $expense = $this->expenseService->update($expense, $request->validated());

        return $this->successResponse(new ExpenseResource($expense), 'Expense updated successfully');
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $this->expenseService->delete($expense);

        $this->activityLogService->logDelete('expense', 'expense', $expense->id);

        return $this->successResponse(null, 'Expense deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $expense = $this->expenseService->findTrashed($id);

        $this->authorize('restore', $expense);

        $expense = $this->expenseService->restore($expense);

        return $this->successResponse(new ExpenseResource($expense), 'Expense restored successfully');
    }

    public function downloadAttachment(Expense $expense)
    {
        $this->authorize('view', $expense);

        if (! $expense->attachment || ! Storage::disk('local')->exists($expense->attachment)) {
            abort(404, 'Attachment not found.');
        }

        $filename = str_replace('/', '_', $expense->expense_no) . '.jpg';

        return Storage::disk('local')->download($expense->attachment, $filename);
    }
}
