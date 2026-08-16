<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Order;
use App\Models\Stock;
use App\Services\OrderStockReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockReservationTest extends TestCase
{
    use RefreshDatabase;

    private OrderStockReservationService $reservationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reservationService = app(OrderStockReservationService::class);
    }

    // ===== CORE CONCURRENCY TEST =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_concurrent_orders_cannot_over_reserve_stock(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 5]);

        $orderA = Order::factory()->create(['status' => OrderStatus::ToProcure]);
        $orderB = Order::factory()->create(['status' => OrderStatus::ToProcure]);

        $this->addOrderItem($orderA, $book, 5);
        $this->addOrderItem($orderB, $book, 5);

        // Order A reserves first
        $this->reservationService->reserveForOrder($orderA);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $orderA->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        // Order B should fail to reserve
        $this->expectException(\RuntimeException::class);
        $this->reservationService->reserveForOrder($orderB);
    }

    // ===== MULTI-PRODUCT ATOMICITY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_multi_product_order_requires_all_items_available(): void
    {
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        Stock::create(['book_id' => $bookA->id, 'current_quantity' => 5]);
        Stock::create(['book_id' => $bookB->id, 'current_quantity' => 2]); // Insufficient

        $order = Order::factory()->create(['status' => OrderStatus::ToProcure]);
        $this->addOrderItem($order, $bookA, 5);
        $this->addOrderItem($order, $bookB, 3); // Requires 3, only 2 available

        $this->expectException(\RuntimeException::class);
        $this->reservationService->reserveForOrder($order);

        // No partial reservation should exist
        $this->assertDatabaseCount('order_stock_reservations', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_multi_product_order_reserves_all_items_atomically(): void
    {
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        Stock::create(['book_id' => $bookA->id, 'current_quantity' => 5]);
        Stock::create(['book_id' => $bookB->id, 'current_quantity' => 3]);

        $order = Order::factory()->create(['status' => OrderStatus::ToProcure]);
        $this->addOrderItem($order, $bookA, 5);
        $this->addOrderItem($order, $bookB, 3);

        $this->reservationService->reserveForOrder($order);

        $this->assertDatabaseCount('order_stock_reservations', 2);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $bookA->id,
            'quantity' => 5,
            'status' => 'active',
        ]);
    }

    // ===== RELEASE RESERVATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_release_reservation_does_not_change_physical_stock(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = Order::factory()->create(['status' => OrderStatus::ToPack]);
        $this->addOrderItem($order, $book, 5);

        $this->reservationService->reserveForOrder($order);
        $this->assertEquals(5, $this->reservationService->getActiveReservationQuantity($book->id));

        $this->reservationService->releaseForOrder($order);

        $this->assertEquals(0, $this->reservationService->getActiveReservationQuantity($book->id));
        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
    }

    // ===== CONSUME RESERVATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consume_reservation_marks_as_consumed(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = Order::factory()->create(['status' => OrderStatus::ToPack]);
        $this->addOrderItem($order, $book, 5);

        $this->reservationService->reserveForOrder($order);

        // Simulate inventory transaction
        $transaction = \App\Models\InventoryTransaction::create([
            'transaction_no' => 'TEST-001',
            'transaction_type' => 'sale',
            'book_id' => $book->id,
            'quantity_in' => 0,
            'quantity_out' => 5,
            'balance_after' => 5,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => 1,
        ]);

        $this->reservationService->consumeForBook($book->id, 5, $transaction->id);

        $this->assertEquals(0, $this->reservationService->getActiveReservationQuantity($book->id));
        $this->assertDatabaseCount('order_stock_reservations', 1);
        $this->assertDatabaseHas('order_stock_reservations', [
            'book_id' => $book->id,
            'status' => 'consumed',
            'consumed_by_inventory_transaction_id' => $transaction->id,
        ]);
    }

    // ===== PURCHASE-DRIVEN ALLOCATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_purchase_increase_enables_allocation(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 4]);

        $order = Order::factory()->create(['status' => OrderStatus::ToProcure]);
        $this->addOrderItem($order, $book, 10);

        $this->assertFalse($this->reservationService->canFullyAllocate($order));

        // Simulate purchase adding 6 units
        $this->app->make(\App\Services\InventoryService::class)->increaseStock(
            $book->id,
            6,
            \App\Enums\InventoryTransactionType::PURCHASE,
            'purchase',
            1,
            now()->format('Y-m-d'),
            'Test purchase',
            1
        );

        $this->assertTrue($this->reservationService->canFullyAllocate($order));
        $this->reservationService->reserveForOrder($order);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 10,
            'status' => 'active',
        ]);
    }

    // ===== FIFO ALLOCATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fifo_allocation_among_competing_orders(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $orderA = Order::factory()->create(['status' => OrderStatus::ToProcure, 'created_at' => now()->subDays(2)]);
        $orderB = Order::factory()->create(['status' => OrderStatus::ToProcure, 'created_at' => now()->subDay()]);

        $this->addOrderItem($orderA, $book, 7);
        $this->addOrderItem($orderB, $book, 5);

        // Order A (older) should get allocated first
        $this->reservationService->reserveForOrder($orderA);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $orderA->id,
            'book_id' => $book->id,
            'quantity' => 7,
            'status' => 'active',
        ]);

        // Order B should fail (only 3 remaining, needs 5)
        $this->expectException(\RuntimeException::class);
        $this->reservationService->reserveForOrder($orderB);
    }

    // ===== IDEMPOTENCY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_reservation_is_idempotent(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = Order::factory()->create(['status' => OrderStatus::ToProcure]);
        $this->addOrderItem($order, $book, 5);

        $this->reservationService->reserveForOrder($order);
        $this->assertDatabaseCount('order_stock_reservations', 1);

        // Calling again should not create duplicate
        $this->reservationService->reserveForOrder($order);
        $this->assertDatabaseCount('order_stock_reservations', 1);
    }

    // ===== SELF-RESERVATION EXCLUSION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_availability_excludes_own_reservation(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = Order::factory()->create(['status' => OrderStatus::ToPack]);
        $this->addOrderItem($order, $book, 5);

        $this->reservationService->reserveForOrder($order);

        // Verify reservation was created
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        // When excluding own reservation, available = 10 (no other reservations)
        $available = $this->reservationService->getAvailableQuantity($book->id, $order->id);
        $this->assertEquals(10, $available);
    }

    // ===== HELPER METHODS =====

    private function addOrderItem(Order $order, Book $book, int $quantity): void
    {
        $order->items()->create([
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => $book->title,
            'unit' => 'pcs',
            'ordered_quantity' => $quantity,
            'remaining_order_quantity' => $quantity,
            'unit_price' => 100,
            'price_snapshot' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'discount_snapshot' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'tax_snapshot' => 0,
            'line_total' => $quantity * 100,
            'remarks' => null,
            'sort_order' => 1,
            'book_id' => $book->id,
        ]);
    }
}