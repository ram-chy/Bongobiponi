<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use App\Services\SafeDeleteEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly SafeDeleteEngine $safeDeleteEngine,
    ) {}

    public function index(Request $request): BookCollection
    {
        $this->authorize('viewAny', Book::class);

        $books = $this->bookService->list($request->only([
            'search', 'status', 'publisher_id', 'category_id', 'author_id', 'language', 'sort', 'direction', 'per_page',
        ]));

        return new BookCollection($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $this->authorize('create', Book::class);

        $book = $this->bookService->store($request->validated());

        return $this->successResponse(new BookResource($book), 'Book created successfully', 201);
    }

    public function show(Book $book): JsonResponse
    {
        $this->authorize('view', $book);

        $book->load(['creator', 'updater', 'publisher', 'category', 'authors']);

        return $this->successResponse(new BookResource($book), 'Book retrieved successfully');
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->authorize('update', $book);

        $book = $this->bookService->update($book, $request->validated());

        return $this->successResponse(new BookResource($book), 'Book updated successfully');
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $result = $this->safeDeleteEngine->delete($book);

        if ($result->success) {
            return $this->successResponse(null, $result->message);
        }

        return $this->deleteErrorResponse($result->toArray());
    }

    public function restore(int $id): JsonResponse
    {
        // CRIT-018 fix: Authorize BEFORE restoring
        $book = Book::withTrashed()->findOrFail($id);
        $this->authorize('restore', $book);
        $this->bookService->restore($id);

        return $this->successResponse(new BookResource($book->fresh()->load(['creator', 'updater', 'publisher', 'category', 'authors'])), 'Book restored successfully');
    }
}
