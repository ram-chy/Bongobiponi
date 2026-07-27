<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublisherRequest;
use App\Http\Requests\UpdatePublisherRequest;
use App\Http\Resources\PublisherCollection;
use App\Http\Resources\PublisherResource;
use App\Models\Publisher;
use App\Services\PublisherService;
use App\Services\SafeDeleteEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublisherController extends Controller
{
    public function __construct(
        private readonly PublisherService $publisherService,
        private readonly SafeDeleteEngine $safeDeleteEngine,
    ) {}

    public function index(Request $request): PublisherCollection
    {
        $this->authorize('viewAny', Publisher::class);

        $publishers = $this->publisherService->list($request->only([
            'search', 'status', 'sort', 'direction', 'per_page',
        ]));

        return new PublisherCollection($publishers);
    }

    public function store(StorePublisherRequest $request): JsonResponse
    {
        $this->authorize('create', Publisher::class);

        $publisher = $this->publisherService->store([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse(new PublisherResource($publisher), 'Publisher created successfully', 201);
    }

    public function show(Publisher $publisher): JsonResponse
    {
        $this->authorize('view', $publisher);

        $publisher->load(['creator', 'updater']);

        return $this->successResponse(new PublisherResource($publisher), 'Publisher retrieved successfully');
    }

    public function update(UpdatePublisherRequest $request, Publisher $publisher): JsonResponse
    {
        $this->authorize('update', $publisher);

        $publisher = $this->publisherService->update($publisher, $request->validated());

        return $this->successResponse(new PublisherResource($publisher), 'Publisher updated successfully');
    }

    public function destroy(Publisher $publisher): JsonResponse
    {
        $this->authorize('delete', $publisher);

        $result = $this->safeDeleteEngine->delete($publisher);

        if ($result->success) {
            return $this->successResponse(null, $result->message);
        }

        return $this->deleteErrorResponse($result->toArray());
    }

    public function restore(int $id): JsonResponse
    {
        // CRIT-018 fix: Authorize BEFORE restoring
        $publisher = Publisher::withTrashed()->findOrFail($id);
        $this->authorize('restore', $publisher);
        $this->publisherService->restore($id);

        return $this->successResponse(new PublisherResource($publisher->fresh()->load(['creator', 'updater'])), 'Publisher restored successfully');
    }
}
