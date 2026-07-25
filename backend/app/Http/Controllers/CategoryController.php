<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): CategoryCollection
    {
        $this->authorize('viewAny', Category::class);

        $categories = $this->categoryService->list($request->only([
            'search', 'status', 'parent_id', 'sort', 'direction', 'per_page',
        ]));

        return new CategoryCollection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $this->categoryService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new CategoryResource($category), 'Category created successfully', 201);
    }

    public function show(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        $category->load(['creator', 'updater', 'parent', 'children']);

        return $this->successResponse(new CategoryResource($category), 'Category retrieved successfully');
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('update', $category);

        $category = $this->categoryService->update($category, $request->validated());

        return $this->successResponse(new CategoryResource($category), 'Category updated successfully');
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        // CRIT-018 fix: Authorize BEFORE restoring
        $category = Category::withTrashed()->findOrFail($id);
        $this->authorize('restore', $category);
        $this->categoryService->restore($id);

        return $this->successResponse(new CategoryResource($category->fresh()->load(['creator', 'updater', 'parent', 'children'])), 'Category restored successfully');
    }
}
