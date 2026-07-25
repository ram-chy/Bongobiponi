<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryCollection;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;
use App\Services\ExpenseCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private readonly ExpenseCategoryService $expenseCategoryService,
    ) {}

    public function index(Request $request): ExpenseCategoryCollection
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        $categories = $this->expenseCategoryService->list($request->only([
            'search', 'sort', 'direction', 'per_page',
        ]));

        return new ExpenseCategoryCollection($categories);
    }

    public function store(StoreExpenseCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', ExpenseCategory::class);

        $category = $this->expenseCategoryService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new ExpenseCategoryResource($category), 'Expense category created successfully', 201);
    }

    public function show(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('view', $expenseCategory);

        $expenseCategory->load('creator');

        return $this->successResponse(new ExpenseCategoryResource($expenseCategory), 'Expense category retrieved successfully');
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('update', $expenseCategory);

        $category = $this->expenseCategoryService->update($expenseCategory, $request->validated());

        return $this->successResponse(new ExpenseCategoryResource($category), 'Expense category updated successfully');
    }

    public function destroy(ExpenseCategory $expenseCategory): JsonResponse
    {
        $this->authorize('delete', $expenseCategory);

        $this->expenseCategoryService->delete($expenseCategory);

        return $this->successResponse(null, 'Expense category deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $category = $this->expenseCategoryService->findTrashed($id);

        $this->authorize('restore', $category);

        $category = $this->expenseCategoryService->restore($category);

        return $this->successResponse(new ExpenseCategoryResource($category), 'Expense category restored successfully');
    }
}
