<?php

namespace Tests\Feature;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryChallanInventoryTest extends TestCase
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

    private function createOrderWithBookItem(Book $book, float $quantity): Order
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::Intake->value,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'book_id' => $book->id,
            'description' => $book->title,
            'unit' => 'pcs',
            'ordered_quantity' => $quantity,
            'remaining_order_quantity' => $quantity,
            'unit_price' => $book->selling_price,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => $quantity * (float) $book->selling_price,
            'sort_order' => 1,
        ]);

        return $order;
    }

    private function createDeliveryChallan(Order $order): int
    {
        $orderItem = $order->items()->first();

        $response = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_address' => 'STEP16 Test Address',
            'items' => [
                [
                    'order_booking_item_id' => $orderItem->id,
                    'delivered_quantity' => $orderItem->ordered_quantity,
                ],
            ],
        ], $this->authHeaders());

        $response->assertCreated();

        return $response->json('data.id');
    }

    public function test_delivery_deducts_stock_and_creates_sale_transaction(): void
    {
        $book = Book::factory()->create(['purchase_price' => 50, 'selling_price' => 100]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = $this->createOrderWithBookItem($book, 5);

        $this->postJson("/api/orders/{$order->id}/status", [
            'status' => OrderStatus::ToPack->value,
        ], $this->authHeaders())->assertOk();

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        $deliveryChallanId = $this->createDeliveryChallan($order);

        $this->assertEquals(5, Stock::where('book_id', $book->id)->first()->current_quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => InventoryTransactionType::SALE->value,
            'reference_type' => 'delivery_challan',
            'book_id' => $book->id,
            'quantity_in' => 0,
            'quantity_out' => 5,
            'balance_after' => 5,
        ]);

        $saleTransactionId = \App\Models\InventoryTransaction::where('reference_type', 'delivery_challan')
            ->where('reference_id', $deliveryChallanId)
            ->first('id')->id;

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'status' => 'consumed',
            'consumed_by_inventory_transaction_id' => $saleTransactionId,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Delivered->value,
        ]);
    }

    public function test_deleting_delivery_challan_restores_stock_and_reverses_sale(): void
    {
        $book = Book::factory()->create(['purchase_price' => 50, 'selling_price' => 100]);
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = $this->createOrderWithBookItem($book, 5);

        $this->postJson("/api/orders/{$order->id}/status", [
            'status' => OrderStatus::ToPack->value,
        ], $this->authHeaders())->assertOk();

        $deliveryChallanId = $this->createDeliveryChallan($order);

        $this->assertEquals(5, Stock::where('book_id', $book->id)->first()->current_quantity);

        $this->deleteJson("/api/delivery-challans/{$deliveryChallanId}", [], $this->authHeaders())
            ->assertOk();

        $this->assertEquals(10, Stock::where('book_id', $book->id)->first()->current_quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => InventoryTransactionType::SALE->value,
            'reference_type' => 'delivery_challan',
            'quantity_out' => 5,
            'quantity_in' => 0,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'transaction_type' => InventoryTransactionType::SALE->value,
            'reference_type' => 'delivery_challan',
            'quantity_in' => 5,
            'quantity_out' => 0,
            'balance_after' => 10,
        ]);

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
            'consumed_by_inventory_transaction_id' => null,
        ]);

        $this->assertEquals(5, $this->app->make(\App\Services\OrderStockReservationService::class)
            ->getActiveReservationQuantity($book->id));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Intake->value,
        ]);
    }

    public function test_delivery_without_book_reference_does_not_touch_inventory(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'book_id' => null,
            'description' => 'Legacy item without book',
            'unit' => 'pcs',
            'ordered_quantity' => 3,
            'remaining_order_quantity' => 3,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => 300,
            'sort_order' => 1,
        ]);

        $this->createDeliveryChallan($order);

        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_damage_stock_out_does_not_consume_reservations(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $order = $this->createOrderWithBookItem($book, 5);

        $this->postJson("/api/orders/{$order->id}/status", [
            'status' => OrderStatus::ToPack->value,
        ], $this->authHeaders())->assertOk();

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'status' => 'active',
        ]);

        $this->app->make(\App\Services\InventoryService::class)->decreaseStock(
            bookId: $book->id,
            quantity: 2,
            type: \App\Enums\InventoryTransactionType::DAMAGE,
            referenceType: null,
            referenceId: null,
            transactionDate: now()->format('Y-m-d'),
            remarks: 'Test damage',
        );

        $this->assertEquals(8, Stock::where('book_id', $book->id)->first()->current_quantity);

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $order->id,
            'book_id' => $book->id,
            'quantity' => 5,
            'status' => 'active',
            'consumed_by_inventory_transaction_id' => null,
        ]);

        $this->assertEquals(5, $this->app->make(\App\Services\OrderStockReservationService::class)
            ->getActiveReservationQuantity($book->id));
    }

    public function test_delivery_consumes_delivering_orders_own_reservation_first(): void
    {
        $book = Book::factory()->create();
        Stock::create(['book_id' => $book->id, 'current_quantity' => 10]);

        $earlierOrder = $this->createOrderWithBookItem($book, 3);
        $this->postJson("/api/orders/{$earlierOrder->id}/status", [
            'status' => OrderStatus::ToPack->value,
        ], $this->authHeaders())->assertOk();

        $deliveringOrder = $this->createOrderWithBookItem($book, 7);
        $this->postJson("/api/orders/{$deliveringOrder->id}/status", [
            'status' => OrderStatus::ToPack->value,
        ], $this->authHeaders())->assertOk();

        $this->assertEquals(10, $this->app->make(\App\Services\OrderStockReservationService::class)
            ->getActiveReservationQuantity($book->id));

        $deliveryOrderItem = $deliveringOrder->items()->first();

        $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_address' => 'STEP17 Cross-Order Test',
            'items' => [
                [
                    'order_booking_item_id' => $deliveryOrderItem->id,
                    'delivered_quantity' => 5,
                ],
            ],
        ], $this->authHeaders())->assertCreated();

        $this->assertEquals(5, Stock::where('book_id', $book->id)->first()->current_quantity);

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $deliveringOrder->id,
            'book_id' => $book->id,
            'quantity' => 2,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('order_stock_reservations', [
            'order_id' => $earlierOrder->id,
            'book_id' => $book->id,
            'quantity' => 3,
            'status' => 'active',
        ]);

        $this->assertEquals(5, $this->app->make(\App\Services\OrderStockReservationService::class)
            ->getActiveReservationQuantity($book->id));
    }
}
