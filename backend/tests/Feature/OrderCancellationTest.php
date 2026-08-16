<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Stock;
use App\Models\User;
use App\Services\OrderStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role_id' => 1]);
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);
    }

    private function authHeaders(?User $user = null): array
    {
        $token = auth('api')->login($user ?? $this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeOrder(string $status = 'to_procure', ?User $owner = null): Order
    {
        return Order::factory()->create([
            'status' => $status,
            'created_by' => ($owner ?? $this->user)->id,
            'customer_id' => $this->customer->id,
        ]);
    }

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

    private function reserveForOrder(Order $order): void
    {
        $this->actingAs($this->user, 'api');
        app(OrderStatusTransitionService::class)->transition($order, OrderStatus::ToPack);
    }

    private function cancelViaApi(Order $order, ?User $actor = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ], $this->authHeaders($actor));
    }

    // ===== BASIC CANCELLATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancel_to_procure_order_sets_cancelled_and_records_history(): void
    {
        $order = $this->makeOrder('to_procure');

        $response = $this->cancelViaApi($order);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'to_procure',
            'to_status' => 'cancelled',
            'changed_by' => $this->user->id,
            'reason' => 'Order cancelled',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_intake_order_can_be_cancelled_without_reservation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('intake');
        $this->addOrderItem($order, $book, 5);

        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('order_stock_reservations', 0);
        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
    }

    // ===== RESERVATION RELEASE =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancellation_releases_active_reservation_without_changing_stock(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'released',
        ]);

        $this->assertDatabaseMissing('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'active',
        ]);

        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
        $this->assertEquals(0, app(\App\Services\OrderStockReservationService::class)->getActiveReservationQuantity($book->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancellation_releases_all_items_atomically(): void
    {
        $bookA = Book::factory()->create(['created_by' => $this->user->id]);
        $bookB = Book::factory()->create(['created_by' => $this->user->id]);
        $bookC = Book::factory()->create(['created_by' => $this->user->id]);

        Stock::create(['book_id' => $bookA->id, 'current_quantity' => 5]);
        Stock::create(['book_id' => $bookB->id, 'current_quantity' => 3]);
        Stock::create(['book_id' => $bookC->id, 'current_quantity' => 2]);

        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $bookA, 5);
        $this->addOrderItem($order, $bookB, 3);
        $this->addOrderItem($order, $bookC, 2);

        $this->reserveForOrder($order);
        $this->assertDatabaseCount('order_stock_reservations', 3);

        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseCount('order_stock_reservations', 3);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $order->id, 'book_id' => $bookA->id, 'status' => 'released']);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $order->id, 'book_id' => $bookB->id, 'status' => 'released']);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $order->id, 'book_id' => $bookC->id, 'status' => 'released']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancellation_does_not_create_inventory_transaction(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancelled_order_reservation_display_shows_released(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);
        $this->cancelViaApi($order)->assertOk();

        $reservations = app(\App\Services\OrderStockReservationService::class)->getReservationsForOrder($order->fresh());

        $this->assertCount(1, $reservations);
        $this->assertSame('released', $reservations[0]['status']);
        $this->assertSame(0, $reservations[0]['reserved_quantity']);
    }

    // ===== CANCELLED ORDER INVARIANT =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancelled_order_never_holds_active_reservations(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);
        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseMissing('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'active',
        ]);
    }

    // ===== RE-ALLOCATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_competing_order_is_allocated_after_cancellation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 5]);

        $orderA = $this->makeOrder('to_procure');
        $this->addOrderItem($orderA, $book, 5);

        $orderB = $this->makeOrder('to_procure');
        $this->addOrderItem($orderB, $book, 5);

        $this->reserveForOrder($orderA);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $orderA->id, 'status' => 'active']);

        $this->cancelViaApi($orderA)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $orderA->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $orderA->id, 'status' => 'released']);

        $this->assertDatabaseHas('orders', ['id' => $orderB->id, 'status' => 'to_pack']);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $orderB->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_fifo_allocation_after_cancellation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 5]);

        $orderA = $this->makeOrder('to_procure');
        $orderA->update(['created_at' => now()->subDays(3)]);
        $this->addOrderItem($orderA, $book, 5);

        $orderB = $this->makeOrder('to_procure');
        $orderB->update(['created_at' => now()->subDays(2)]);
        $this->addOrderItem($orderB, $book, 5);

        $orderC = $this->makeOrder('to_procure');
        $orderC->update(['created_at' => now()->subDay()]);
        $this->addOrderItem($orderC, $book, 5);

        $this->reserveForOrder($orderA);
        $this->cancelViaApi($orderA)->assertOk();

        // Oldest waiting order (B) receives the released stock; C stays waiting.
        $this->assertDatabaseHas('orders', ['id' => $orderB->id, 'status' => 'to_pack']);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $orderB->id, 'quantity' => 5, 'status' => 'active']);
        $this->assertDatabaseHas('orders', ['id' => $orderC->id, 'status' => 'to_procure']);
        $this->assertDatabaseMissing('order_stock_reservations', ['order_id' => $orderC->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_no_partial_reallocation_for_multi_book_order(): void
    {
        $bookA = Book::factory()->create(['created_by' => $this->user->id]);
        $bookB = Book::factory()->create(['created_by' => $this->user->id]);

        Stock::create(['book_id' => $bookA->id, 'current_quantity' => 5]);
        Stock::create(['book_id' => $bookB->id, 'current_quantity' => 0]);

        $orderA = $this->makeOrder('to_procure');
        $this->addOrderItem($orderA, $bookA, 5);

        $orderX = $this->makeOrder('to_procure');
        $this->addOrderItem($orderX, $bookA, 5);
        $this->addOrderItem($orderX, $bookB, 5);

        $this->reserveForOrder($orderA);
        $this->cancelViaApi($orderA)->assertOk();

        // Order X needs Book B too, which is unavailable, so it must stay TO_PROCURE
        // and must not be partially reserved.
        $this->assertDatabaseHas('orders', ['id' => $orderX->id, 'status' => 'to_procure']);
        $this->assertDatabaseMissing('order_stock_reservations', ['order_id' => $orderX->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_reallocation_failure_does_not_undo_cancellation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 5]);

        $orderA = $this->makeOrder('to_procure');
        $this->addOrderItem($orderA, $book, 5);

        $orderB = $this->makeOrder('to_procure');
        $this->addOrderItem($orderB, $book, 5);

        $this->reserveForOrder($orderA);

        // Simulate a failure that occurs only during Order B's re-allocation
        // transition (after Order A's cancellation has already committed).
        Event::listen('eloquent.creating: ' . OrderStatusHistory::class, function (OrderStatusHistory $history) use ($orderB): void {
            if ($history->order_id === $orderB->id) {
                throw new RuntimeException('simulated re-allocation failure');
            }
        });

        try {
            $this->cancelViaApi($orderA)->assertOk();
        } finally {
            Event::forget('eloquent.creating: ' . OrderStatusHistory::class);
        }

        $this->assertDatabaseHas('orders', ['id' => $orderA->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $orderA->id, 'status' => 'released']);
        $this->assertDatabaseHas('orders', ['id' => $orderB->id, 'status' => 'to_procure']);
    }

    // ===== INVALID TRANSITIONS =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_packed_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder('packed');

        $this->cancelViaApi($order)->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => "Cannot transition order from 'packed' to 'cancelled'.",
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'packed']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_dispatched_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder('dispatched');

        $this->cancelViaApi($order)->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'dispatched']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder('delivered');

        $this->cancelViaApi($order)->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);
    }

    // ===== IDEMPOTENCY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_repeated_cancellation_is_rejected_and_creates_single_history(): void
    {
        $order = $this->makeOrder('to_procure');

        $this->cancelViaApi($order)->assertOk();
        $this->cancelViaApi($order)->assertStatus(422);

        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'cancelled',
        ]);
    }

    // ===== CONSUMED RESERVATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_consumed_reservation_is_not_released_by_cancellation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);

        $transaction = InventoryTransaction::create([
            'transaction_no' => 'CANCEL-CONSUMED-001',
            'transaction_type' => 'sale',
            'book_id' => $book->id,
            'quantity_in' => 0,
            'quantity_out' => 5,
            'balance_after' => 5,
            'transaction_date' => now()->format('Y-m-d'),
            'created_by' => $this->user->id,
        ]);

        app(\App\Services\OrderStockReservationService::class)->consumeForBook($book->id, 5, $transaction->id);

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'consumed',
        ]);

        $this->cancelViaApi($order)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'consumed',
        ]);
        $this->assertDatabaseMissing('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
    }

    // ===== ATOMICITY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancellation_release_and_history_are_atomic(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reserveForOrder($order);
        $this->assertDatabaseHas('order_stock_reservations', ['order_id' => $order->id, 'status' => 'active']);

        Event::listen('eloquent.creating: ' . OrderStatusHistory::class, function (): never {
            throw new RuntimeException('simulated history write failure');
        });

        try {
            $this->actingAs($this->user, 'api');
            app(OrderStatusTransitionService::class)->transition($order->fresh(), OrderStatus::Cancelled);
            $this->fail('Expected the history failure to abort the cancellation.');
        } catch (RuntimeException) {
            // expected
        } finally {
            Event::forget('eloquent.creating: ' . OrderStatusHistory::class);
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'to_pack']);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'active',
            'quantity' => 5,
        ]);
        // Only the pre-cancellation to_pack transition history remains.
        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'cancelled',
        ]);
    }

    // ===== AUTHORIZATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_regular_user_cannot_cancel_another_users_order(): void
    {
        $order = $this->makeOrder('to_procure');

        $otherUser = User::factory()->create(['role_id' => 3]);

        $this->cancelViaApi($order, $otherUser)->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'to_procure']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_owner_regular_user_can_cancel_own_order(): void
    {
        $owner = User::factory()->create(['role_id' => 3]);
        $order = $this->makeOrder('to_procure', $owner);

        $this->cancelViaApi($order, $owner)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }
}
