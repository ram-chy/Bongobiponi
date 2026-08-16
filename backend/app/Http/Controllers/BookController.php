<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Services\BookService;
use App\Services\SafeDeleteEngine;
use App\Services\SpreadsheetExporterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly SafeDeleteEngine $safeDeleteEngine,
        private readonly SpreadsheetExporterService $spreadsheetExporter,
    ) {}

    public function index(Request $request): BookCollection
    {
        $this->authorize('viewAny', Book::class);

        $books = $this->bookService->list($request->only([
            'search', 'publisher_id', 'category_id', 'author_id', 'language', 'sort', 'direction', 'per_page',
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

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Book::class);

        $books = Book::query()
            ->with(['publisher', 'category', 'authors', 'stock'])
            ->orderBy('title')
            ->get();

        $headers = [
            'Title', 'Subtitle', 'ISBN', 'Barcode', 'Publisher', 'Category', 'Authors',
            'Edition', 'Language', 'Purchase Price', 'Selling Price', 'Minimum Stock',
            'Current Stock', 'Description',
        ];

        $rows = $books->map(function (Book $book): array {
            return [
                $book->title,
                $book->subtitle ?? '',
                $book->isbn ?? '',
                $book->barcode ?? '',
                $book->publisher?->name ?? '',
                $book->category?->name ?? '',
                $book->authors->pluck('name')->join(', '),
                $book->edition ?? '',
                $book->language ?? '',
                (float) $book->purchase_price,
                (float) $book->selling_price,
                (int) $book->minimum_stock,
                (int) ($book->stock?->current_quantity ?? 0),
                $book->description ?? '',
            ];
        })->all();

        $filename = 'book-catalogue-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($headers, $rows) {
            echo $this->spreadsheetExporter->toXlsx($headers, $rows, 'Catalogue');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function uploadCover(Request $request): JsonResponse
    {
        $this->authorize('create', Book::class);

        $request->validate([
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $file = $request->file('cover_image');
        $tempName = 'temp_' . time() . '_' . uniqid() . '.' . $file->extension();
        $path = $file->storeAs('covers/temp', $tempName, 'public');

        $fullPath = Storage::disk('public')->path($path);
        $this->compressImage($fullPath);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data' => [
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
            ],
        ]);
    }

    private function compressImage(string $path, int $maxSize = 500 * 1024): void
    {
        $info = getimagesize($path);
        if (!$info) return;

        $mime = $info['mime'];
        $quality = 85;

        do {
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($path),
                'image/png' => imagecreatefrompng($path),
                'image/webp' => imagecreatefromwebp($path),
                default => null,
            };

            if (!$image) break;

            match ($mime) {
                'image/jpeg' => imagejpeg($image, $path, $quality),
                'image/png' => imagepng($image, $path, max(1, (int) round(9 - ($quality / 10)))),
                'image/webp' => imagewebp($image, $path, $quality),
                default => null,
            };

            imagedestroy($image);

            $size = filesize($path);
            $quality -= 5;
        } while ($size > $maxSize && $quality > 10);
    }
}
