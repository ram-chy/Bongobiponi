<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorRequest;
use App\Http\Requests\UpdateAuthorRequest;
use App\Http\Resources\AuthorCollection;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthorController extends Controller
{
    public function __construct(
        private readonly AuthorService $authorService,
    ) {}

    public function index(Request $request): AuthorCollection
    {
        $this->authorize('viewAny', Author::class);

        $authors = $this->authorService->list($request->only([
            'search', 'status', 'sort', 'direction', 'per_page',
        ]));

        return new AuthorCollection($authors);
    }

    public function store(StoreAuthorRequest $request): JsonResponse
    {
        $this->authorize('create', Author::class);

        $author = $this->authorService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new AuthorResource($author), 'Author created successfully', 201);
    }

    public function show(Author $author): JsonResponse
    {
        $this->authorize('view', $author);

        $author->load(['creator', 'updater', 'books']);

        return $this->successResponse(new AuthorResource($author), 'Author retrieved successfully');
    }

    public function update(UpdateAuthorRequest $request, Author $author): JsonResponse
    {
        $this->authorize('update', $author);

        $author = $this->authorService->update($author, $request->validated());

        return $this->successResponse(new AuthorResource($author), 'Author updated successfully');
    }

    public function destroy(Author $author): JsonResponse
    {
        $this->authorize('delete', $author);

        $author->delete();

        return $this->successResponse(null, 'Author deleted successfully');
    }

    public function restore(int $id): JsonResponse
    {
        $author = $this->authorService->restore($id);

        $this->authorize('restore', $author);

        return $this->successResponse(new AuthorResource($author), 'Author restored successfully');
    }
}
