<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\OrderStatusTransitionService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class OrderStatusHistoryTest extends TestCase
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

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeOrder(string $status = 'intake'): Order
    {
        return Order::factory()->create([
            'status' => $status,
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    private function makeOrderWithBook(string $status, Book $book, int $quantity): Order
    {
        $order = $this->makeOrder($status);

        OrderItem::create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => 'Test Book',
            'unit' => 'pcs',
            'ordered_quantity' => $quantity,
            'remaining_order_quantity' => $quantity,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => 100 * $quantity,
            'sort_order' => 1,
        ]);

        return $order;
    }

    private function makePurchase(array $items): Purchase
    {
        $purchase = Purchase::create([
            'purchase_no' => 'PO-TEST-' . uniqid(),
            'purchase_type' => 'manual',
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->format('Y-m-d'),
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        foreach ($items as $item) {
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'book_id' => $item['book_id'],
                'ordered_quantity' => $item['received_quantity'],
                'received_quantity' => $item['received_quantity'],
                'purchase_price' => 100,
                'total' => 100 * $item['received_quantity'],
            ]);
        }

        return $purchase;
    }

    private function validManualPayload(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'notes' => 'Test order',
            'items' => [
                [
                    'description' => 'Product A',
                    'unit' => 'pcs',
                    'ordered_quantity' => 10,
                    'unit_price' => 100,
                    'discount_percentage' => 10,
                    'tax_percentage' => 18,
                ],
            ],
        ];
    }

    public function test_creating_order_records_initial_intake_history(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $response->json('data.id'),
            'from_status' => null,
            'to_status' => 'intake',
            'changed_by' => $this->user->id,
            'reason' => 'Order created',
        ]);
    }

    public function test_valid_transition_records_history(): void
    {
        $order = $this->makeOrder('intake');
        $service = app(OrderStatusTransitionService::class);

        $this->actingAs($this->user, 'api');
        $service->transition($order, OrderStatus::ToProcure);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'intake',
            'to_status' => 'to_procure',
            'changed_by' => $this->user->id,
            'reason' => 'Order requires procurement',
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'to_procure']);
    }

    public function test_to_procure_to_pack_transition_records_history(): void
    {
        $order = $this->makeOrder('to_procure');
        $service = app(OrderStatusTransitionService::class);

        $this->actingAs($this->user, 'api');
        $service->transition($order, OrderStatus::ToPack);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'to_procure',
            'to_status' => 'to_pack',
        ]);
    }

    public function test_invalid_transition_does_not_record_history(): void
    {
        $order = $this->makeOrder('intake');
        $service = app(OrderStatusTransitionService::class);

        try {
            $service->transition($order, OrderStatus::Delivered);
            $this->fail('Transition intake -> delivered should have been rejected.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertSame('intake', $order->fresh()->status->value);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_system_transition_records_null_actor_without_fake_user(): void
    {
        $order = $this->makeOrder('to_procure');
        $service = app(OrderStatusTransitionService::class);

        $service->transitionBySystem($order, OrderStatus::ToPack, 'Order became fully available');

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'to_procure',
            'to_status' => 'to_pack',
            'changed_by' => null,
            'reason' => 'Order became fully available',
        ]);
    }

    public function test_status_update_and_history_creation_are_atomic(): void
    {
        Event::listen('eloquent.creating: ' . OrderStatusHistory::class, function (): never {
            throw new \RuntimeException('simulated history write failure');
        });

        try {
            $order = $this->makeOrder('intake');
            app(OrderStatusTransitionService::class)->transition($order, OrderStatus::ToProcure);
            $this->fail('Expected the history failure to abort the transition.');
        } catch (\RuntimeException) {
            // expected
        } finally {
            Event::forget('eloquent.creating: ' . OrderStatusHistory::class);
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'intake']);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_api_requires_authentication_for_history(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->getJson("/api/orders/{$order->id}/status-history");

        $response->assertUnauthorized();
    }

    public function test_api_regular_user_cannot_view_other_users_order_history(): void
    {
        $order = $this->makeOrder('intake');

        $otherUser = User::factory()->create(['role_id' => 3]);
        $token = auth('api')->login($otherUser);

        $response = $this->getJson("/api/orders/{$order->id}/status-history", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertNotFound();
    }

    public function test_api_returns_empty_history_for_legacy_order(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->getJson("/api/orders/{$order->id}/status-history", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_api_returns_history_after_transition(): void
    {
        $order = $this->makeOrder('intake');

        $this->postJson("/api/orders/{$order->id}/status", ['status' => 'to_pack'], $this->authHeaders())
            ->assertOk();

        $response = $this->getJson("/api/orders/{$order->id}/status-history", $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.from_status', 'intake')
            ->assertJsonPath('data.0.to_status', 'to_pack')
            ->assertJsonPath('data.0.changed_by.id', $this->user->id)
            ->assertJsonPath('data.0.reason', 'Order ready for packing');
    }

    public function test_api_returns_history_newest_first(): void
    {
        $order = $this->makeOrder('intake');

        $this->postJson("/api/orders/{$order->id}/status", ['status' => 'to_procure'], $this->authHeaders())->assertOk();
        $this->postJson("/api/orders/{$order->id}/status", ['status' => 'to_pack'], $this->authHeaders())->assertOk();

        $response = $this->getJson("/api/orders/{$order->id}/status-history", $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.from_status', 'to_procure')
            ->assertJsonPath('data.0.to_status', 'to_pack')
            ->assertJsonPath('data.1.from_status', 'intake')
            ->assertJsonPath('data.1.to_status', 'to_procure');
    }

    public function test_noop_transition_does_not_record_history(): void
    {
        $order = $this->makeOrder('to_pack');
        $service = app(OrderStatusTransitionService::class);

        $this->assertFalse($service->canTransition('to_pack', 'to_pack'));

        try {
            $service->transition($order, OrderStatus::ToPack);
            $this->fail('No-op transition should have been rejected.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_purchase_driven_to_pack_records_system_history(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 0]);
        $order = $this->makeOrderWithBook('to_procure', $book, 5);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);
        app(PurchaseService::class)->confirm($purchase);

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'to_procure',
            'to_status' => 'to_pack',
            'changed_by' => null,
            'reason' => 'Order became fully available',
        ]);
    }

    public function test_partial_procurement_does_not_create_false_to_pack_history(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 4]);
        $order = $this->makeOrderWithBook('to_procure', $book, 10);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 3]]);
        app(PurchaseService::class)->confirm($purchase);

        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);
        $this->assertSame(7, (int) Stock::where('book_id', $book->id)->value('current_quantity'));

        $this->assertDatabaseMissing('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => 'to_pack',
        ]);
    }

    public function test_delivery_challan_completion_records_delivered_history(): void
    {
        $order = $this->makeOrder('intake');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => 'Test Book',
            'unit' => 'pcs',
            'ordered_quantity' => 10,
            'remaining_order_quantity' => 10,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => 1000,
            'sort_order' => 1,
        ]);

        $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_address' => 'Test Address',
            'items' => [
                [
                    'order_booking_item_id' => $orderItem->id,
                    'delivered_quantity' => 10,
                ],
            ],
        ], $this->authHeaders())->assertCreated();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered']);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'intake',
            'to_status' => 'delivered',
            'changed_by' => $this->user->id,
            'reason' => 'Order fully delivered',
        ]);
    }

    public function test_deleting_delivery_challan_records_intake_history(): void
    {
        $order = $this->makeOrder('intake');
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => 'Test Book',
            'unit' => 'pcs',
            'ordered_quantity' => 10,
            'remaining_order_quantity' => 10,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => 1000,
            'sort_order' => 1,
        ]);

        $deliveryChallanId = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_address' => 'Test Address',
            'items' => [
                [
                    'order_booking_item_id' => $orderItem->id,
                    'delivered_quantity' => 10,
                ],
            ],
        ], $this->authHeaders())->assertCreated()->json('data.id');

        $this->deleteJson("/api/delivery-challans/{$deliveryChallanId}", [], $this->authHeaders())
            ->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'intake']);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'delivered',
            'to_status' => 'intake',
            'changed_by' => $this->user->id,
            'reason' => 'Order reopened for fulfillment',
        ]);
    }
}
