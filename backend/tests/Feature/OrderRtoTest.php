<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Stock;
use App\Models\User;
use App\Services\OrderStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class OrderRtoTest extends TestCase
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

    private function makeOrder(string $status = 'dispatched', ?User $owner = null): Order
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

    /**
     * Reserve, pack and dispatch a TO_PROCURE order so it holds an ACTIVE
     * reservation while sitting at DISPATCHED.
     */
    private function reservePackAndDispatch(Order $order): void
    {
        $this->actingAs($this->user, 'api');
        app(OrderStatusTransitionService::class)->transition($order, OrderStatus::ToPack);
        app(OrderStatusTransitionService::class)->transition($order->fresh(), OrderStatus::Packed);
        app(OrderStatusTransitionService::class)->transition($order->fresh(), OrderStatus::Dispatched);
    }

    private function markRtoViaApi(Order $order, ?User $actor = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'rto',
        ], $this->authHeaders($actor));
    }

    // ===== BASIC RTO =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_dispatched_order_can_become_rto_and_records_history(): void
    {
        $order = $this->makeOrder('dispatched');

        $response = $this->markRtoViaApi($order);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Order status updated successfully',
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'rto');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rto']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'dispatched',
            'to_status' => 'rto',
            'changed_by' => $this->user->id,
            'reason' => 'Order returned to origin',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_history_is_created_once_with_correct_actor(): void
    {
        $order = $this->makeOrder('dispatched');

        $this->markRtoViaApi($order)->assertOk();

        $history = OrderStatusHistory::where('order_id', $order->id)
            ->where('to_status', 'rto')
            ->get();

        $this->assertCount(1, $history);
        $this->assertSame($this->user->id, $history->first()->changed_by);
        $this->assertSame(OrderStatus::Dispatched, $history->first()->from_status);
        $this->assertSame(OrderStatus::Rto, $history->first()->to_status);
    }

    // ===== IDEMPOTENCY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_repeated_rto_is_rejected_and_creates_single_history(): void
    {
        $order = $this->makeOrder('dispatched');

        $this->markRtoViaApi($order)->assertOk();
        $this->markRtoViaApi($order)->assertStatus(422)
            ->assertJsonPath('message', "Cannot transition order from 'rto' to 'rto'.");

        $this->assertDatabaseCount('order_status_histories', 1);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'rto',
        ]);
    }

    // ===== CANCELLATION SEPARATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_cancelled_order_cannot_become_rto(): void
    {
        $order = $this->makeOrder('cancelled');

        $this->markRtoViaApi($order)->assertStatus(422)
            ->assertJsonPath('message', "Cannot transition order from 'cancelled' to 'rto'.");

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder('dispatched');

        $this->markRtoViaApi($order)->assertOk();

        $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'cancelled',
        ], $this->authHeaders())->assertStatus(422)
            ->assertJsonPath('message', "Cannot transition order from 'rto' to 'cancelled'.");

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rto']);
    }

    // ===== INVALID SOURCE STATUSES =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_unsupported_source_statuses_cannot_become_rto(): void
    {
        foreach (['intake', 'to_procure', 'to_pack', 'packed', 'delivered'] as $status) {
            $order = $this->makeOrder($status);

            $this->markRtoViaApi($order)->assertStatus(422)
                ->assertJsonPath('message', "Cannot transition order from '{$status}' to 'rto'.");

            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $status]);
        }
    }

    // ===== INVENTORY SAFETY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_does_not_change_stock_or_create_inventory_transaction(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reservePackAndDispatch($order);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->markRtoViaApi($order)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rto']);
        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    // ===== RESERVATION AUDIT =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_does_not_release_active_reservation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reservePackAndDispatch($order);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        $this->markRtoViaApi($order)->assertOk();

        // RTO is a status-only outcome: the reservation is neither released nor
        // consumed, and remains untouched until the customer defines a return.
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_does_not_release_consumed_reservation(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);
        $order = $this->makeOrder('to_procure');
        $this->addOrderItem($order, $book, 5);

        $this->reservePackAndDispatch($order);

        $transaction = InventoryTransaction::create([
            'transaction_no' => 'RTO-CONSUMED-001',
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

        $this->markRtoViaApi($order)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rto']);
        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'consumed',
        ]);
        $this->assertDatabaseMissing('order_stock_reservations', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
    }

    // ===== FINANCIAL & DELIVERY CHALLAN SAFETY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_does_not_modify_invoice_or_payment(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'grand_total' => 5000.00,
            'paid_amount' => 0,
            'payment_status' => 'Unpaid',
            'status' => 'issued',
            'created_by' => $this->user->id,
        ]);
        $payment = Payment::factory()->create([
            'customer_id' => $this->customer->id,
            'total_amount' => 5000.00,
            'payment_status' => 'paid',
            'created_by' => $this->user->id,
        ]);

        $order = $this->makeOrder('dispatched');

        $this->markRtoViaApi($order)->assertOk();

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'issued', 'payment_status' => 'Unpaid']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_status' => 'paid']);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_does_not_create_or_modify_delivery_challan(): void
    {
        $challan = DeliveryChallan::create([
            'serial' => 'BBDC/RTO-001',
            'delivery_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Test address',
            'status' => 'dispatched',
            'created_by' => $this->user->id,
        ]);

        $order = $this->makeOrder('dispatched');

        $this->markRtoViaApi($order)->assertOk();

        $this->assertDatabaseHas('delivery_challans', ['id' => $challan->id, 'status' => 'dispatched']);
        $this->assertDatabaseCount('delivery_challans', 1);
    }

    // ===== ATOMICITY =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_rto_status_change_and_history_are_atomic(): void
    {
        $order = $this->makeOrder('dispatched');

        Event::listen('eloquent.creating: ' . OrderStatusHistory::class, function (): never {
            throw new RuntimeException('simulated history write failure');
        });

        try {
            $this->actingAs($this->user, 'api');
            app(OrderStatusTransitionService::class)->transition($order->fresh(), OrderStatus::Rto);
            $this->fail('Expected the history failure to abort the RTO transition.');
        } catch (RuntimeException) {
            // expected
        } finally {
            Event::forget('eloquent.creating: ' . OrderStatusHistory::class);
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'dispatched']);
        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'rto',
        ]);
    }

    // ===== AUTHORIZATION =====

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_regular_user_cannot_mark_another_users_order_as_rto(): void
    {
        $order = $this->makeOrder('dispatched');

        $otherUser = User::factory()->create(['role_id' => 3]);

        $this->markRtoViaApi($order, $otherUser)->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'dispatched']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_owner_regular_user_can_mark_own_order_as_rto(): void
    {
        $owner = User::factory()->create(['role_id' => 3]);
        $order = $this->makeOrder('dispatched', $owner);

        $this->markRtoViaApi($order, $owner)->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rto']);
    }
}
