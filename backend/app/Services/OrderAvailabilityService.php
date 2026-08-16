<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderAvailabilityService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly \App\Services\OrderStockReservationService $reservationService,
    ) {}

    /**
     * Read-only availability calculation. Does not mutate inventory or Order
     * status. Availability is a point-in-time check against current stock.
     *
     * @return array{
     *     order_id: int,
     *     status: string,
     *     fully_available: bool,
     *     items: array<int, array<string, mixed>>,
     * }
     */
    public function check(Order $order): array
    {
        $order->loadMissing(['items.book']);

        $groups = $this->groupByBook($order->items);

        $bookIds = collect($groups)
            ->map(fn (array $group) => $group['book_id'])
            ->filter()
            ->values()
            ->all();

        $stocks = $this->inventoryService->getCurrentStockForBooks($bookIds)->keyBy('book_id');

        $items = [];
        $totalAvailable = 0;
        $totalShortage = 0;
        $unverifiable = false;

        foreach ($groups as $group) {
            $required = round($group['required_quantity'], 2);

            if ($group['book_id'] === null || $group['book'] === null) {
                $unverifiable = true;
                $items[] = [
                    'order_item_ids' => $group['order_item_ids'],
                    'book_id' => $group['book_id'],
                    'book_title' => $group['book']?->title,
                    'required_quantity' => $required,
                    'available_quantity' => 0,
                    'shortage_quantity' => 0,
                    'is_available' => false,
                    'unverifiable' => true,
                ];
                continue;
            }

            $physicalStock = (float) ($stocks->get($group['book_id'])?->current_quantity ?? 0);
            $activeReservations = (float) $this->reservationService->getActiveReservationQuantity($group['book_id'], excludeOrderId: $order->id);
            $available = max(0, $physicalStock - $activeReservations);
            $shortage = round(max($required - $available, 0), 2);

            $totalAvailable += $available;
            $totalShortage += $shortage;

            $items[] = [
                'order_item_ids' => $group['order_item_ids'],
                'book_id' => $group['book_id'],
                'book_title' => $group['book']->title,
                'required_quantity' => $required,
                'available_quantity' => round($available, 2),
                'shortage_quantity' => $shortage,
                'is_available' => $shortage <= 0,
            ];
        }

        $status = $this->determineStatus($unverifiable, $totalShortage, $totalAvailable);

        return [
            'order_id' => $order->id,
            'status' => $status,
            'fully_available' => $status === 'fully_available',
            'items' => $items,
        ];
    }

    private function determineStatus(bool $unverifiable, float $totalShortage, float $totalAvailable): string
    {
        if ($unverifiable) {
            return 'unverifiable';
        }

        if ($totalShortage <= 0) {
            return 'fully_available';
        }

        if ($totalAvailable <= 0) {
            return 'unavailable';
        }

        return 'partially_available';
    }

    /**
     * Group order items by book_id so duplicate Book items share one stock pool.
     *
     * @return array<int, array{
     *     book_id: int|null,
     *     book: object|null,
     *     required_quantity: float,
     *     order_item_ids: array<int, int>,
     * }>
     */
    private function groupByBook(Collection $items): array
    {
        $groups = [];

        foreach ($items as $item) {
            $key = $item->book_id === null ? '__missing_book__' : (string) $item->book_id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'book_id' => $item->book_id,
                    'book' => $item->book,
                    'required_quantity' => 0,
                    'order_item_ids' => [],
                ];
            }

            $groups[$key]['required_quantity'] += (float) $item->remaining_order_quantity;
            $groups[$key]['order_item_ids'][] = $item->id;
        }

        return array_values($groups);
    }
}
