<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Book;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseOrderSynchronizationService;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderSynchronizationTest extends TestCase
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

    private function setStock(Book $book, int $quantity): void
    {
        Stock::create(['book_id' => $book->id, 'current_quantity' => $quantity]);
    }

    private function currentStock(Book $book): int
    {
        return (int) Stock::where('book_id', $book->id)->value('current_quantity');
    }

    private function makeOrder(string $status, array $items): Order
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => $status,
        ]);

        foreach ($items as $index => $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'book_id' => $item['book_id'] ?? null,
                'source_type' => 'manual',
                'item_no' => $index + 1,
                'description' => 'Test Book',
                'unit' => 'pcs',
                'ordered_quantity' => $item['quantity'],
                'remaining_order_quantity' => $item['quantity'],
                'unit_price' => 100,
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'tax_percentage' => 0,
                'tax_amount' => 0,
                'line_total' => 100 * $item['quantity'],
                'sort_order' => $index + 1,
            ]);
        }

        return $order;
    }

    private function makePurchase(array $items, string $status = 'draft'): Purchase
    {
        $purchase = Purchase::create([
            'purchase_no' => 'PO-TEST-' . uniqid(),
            'purchase_type' => 'manual',
            'supplier_id' => Supplier::factory()->create()->id,
            'purchase_date' => now()->format('Y-m-d'),
            'status' => $status,
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

    private function confirm(Purchase $purchase): array
    {
        return app(PurchaseService::class)->confirm($purchase)->toArray();
    }

    private function sync(Purchase $purchase): array
    {
        return app(PurchaseOrderSynchronizationService::class)->syncAfterPurchase($purchase);
    }

    public function test_confirm_with_no_orders_changes_nothing(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $result = $this->sync($purchase);

        $this->assertSame(0, $result['affected_orders']);
        $this->assertSame(0, $result['orders_moved_to_pack']);
    }

    public function test_purchase_fully_satisfying_one_order_moves_it_to_pack(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 5]]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
        $this->assertSame(5, $this->currentStock($book));
    }

    public function test_orders_are_evaluated_independently(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $smallOrder = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 2]]);
        $largeOrder = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 6]]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);

        $this->assertSame(OrderStatus::ToPack, $smallOrder->fresh()->status);
        $this->assertSame(OrderStatus::ToProcure, $largeOrder->fresh()->status);
    }

    public function test_partially_available_order_remains_to_procure(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 2);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 5]]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 2]]);

        $this->confirm($purchase);

        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);
        $this->assertSame(4, $this->currentStock($book));
    }

    public function test_multiple_purchases_eventually_move_order_to_pack(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 10]]);

        $firstPurchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 4]]);
        $this->confirm($firstPurchase);
        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);

        $secondPurchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 6]]);
        $this->confirm($secondPurchase);

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
        $this->assertSame(10, $this->currentStock($book));
    }

    public function test_multi_book_order_requires_all_books_before_moving(): void
    {
        $bookA = Book::factory()->create(['created_by' => $this->user->id]);
        $bookB = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($bookA, 0);
        $this->setStock($bookB, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [
            ['book_id' => $bookA->id, 'quantity' => 5],
            ['book_id' => $bookB->id, 'quantity' => 5],
        ]);

        $purchaseA = $this->makePurchase([['book_id' => $bookA->id, 'received_quantity' => 5]]);
        $this->confirm($purchaseA);
        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);

        $purchaseB = $this->makePurchase([['book_id' => $bookB->id, 'received_quantity' => 5]]);
        $this->confirm($purchaseB);

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
    }

    public function test_unrelated_purchase_does_not_reevaluate_order(): void
    {
        $orderBook = Book::factory()->create(['created_by' => $this->user->id]);
        $unrelatedBook = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($orderBook, 0);
        $this->setStock($unrelatedBook, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $orderBook->id, 'quantity' => 5]]);

        $purchase = $this->makePurchase([['book_id' => $unrelatedBook->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);
        $result = $this->sync($purchase);

        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);
        $this->assertSame(0, $result['affected_orders']);
    }

    public function test_orders_in_terminal_statuses_are_never_reopened(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);

        $packed = $this->makeOrder(OrderStatus::Packed->value, [['book_id' => $book->id, 'quantity' => 1]]);
        $dispatched = $this->makeOrder(OrderStatus::Dispatched->value, [['book_id' => $book->id, 'quantity' => 1]]);
        $delivered = $this->makeOrder(OrderStatus::Delivered->value, [['book_id' => $book->id, 'quantity' => 1]]);
        $rto = $this->makeOrder(OrderStatus::Rto->value, [['book_id' => $book->id, 'quantity' => 1]]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);

        $this->assertSame(OrderStatus::Packed, $packed->fresh()->status);
        $this->assertSame(OrderStatus::Dispatched, $dispatched->fresh()->status);
        $this->assertSame(OrderStatus::Delivered, $delivered->fresh()->status);
        $this->assertSame(OrderStatus::Rto, $rto->fresh()->status);
    }

    public function test_intake_orders_are_not_auto_moved(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $intakeOrder = $this->makeOrder(OrderStatus::Intake->value, [['book_id' => $book->id, 'quantity' => 5]]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);

        $this->assertSame(OrderStatus::Intake, $intakeOrder->fresh()->status);
    }

    public function test_sync_is_idempotent(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 5]]);
        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);
        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
        $transactionCountAfterConfirm = InventoryTransaction::count();

        $result = $this->sync($purchase);

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
        $this->assertSame(0, $result['affected_orders']);
        $this->assertSame(0, $result['orders_moved_to_pack']);
        $this->assertSame($transactionCountAfterConfirm, InventoryTransaction::count());
    }

    public function test_legacy_order_with_missing_book_reference_is_not_moved(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [
            ['book_id' => $book->id, 'quantity' => 5],
            ['book_id' => null, 'quantity' => 1],
        ]);

        $purchase = $this->makePurchase([['book_id' => $book->id, 'received_quantity' => 5]]);

        $this->confirm($purchase);
        $result = $this->sync($purchase);

        $this->assertSame(OrderStatus::ToProcure, $order->fresh()->status);
        $this->assertSame(1, $result['affected_orders']);
        $this->assertSame(0, $result['orders_moved_to_pack']);
    }

    public function test_confirmation_updates_inventory_exactly_once(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 5]]);
        $purchase = $this->makePurchase([
            ['book_id' => $book->id, 'received_quantity' => 3],
            ['book_id' => $book->id, 'received_quantity' => 2],
        ]);

        $this->confirm($purchase);

        $this->assertSame(5, $this->currentStock($book));
        $this->assertSame(2, InventoryTransaction::where('reference_type', Purchase::class)->where('reference_id', $purchase->id)->count());
        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
    }

    public function test_confirm_flow_through_api_triggers_sync(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder(OrderStatus::ToProcure->value, [['book_id' => $book->id, 'quantity' => 5]]);
        $supplier = Supplier::factory()->create(['created_by' => $this->user->id]);
        $publisher = \App\Models\Publisher::factory()->create(['created_by' => $this->user->id]);

        $storeResponse = $this->actingAs($this->user, 'api')->withHeaders($this->authHeaders())
            ->postJson('/api/purchases', [
                'supplier_id' => $supplier->id,
                'publisher_id' => $publisher->id,
                'purchase_date' => now()->format('Y-m-d'),
                'items' => [
                    [
                        'book_id' => $book->id,
                        'received_quantity' => 5,
                        'purchase_price' => 100,
                        'printed_price' => 200,
                        'discount_percentage' => 10,
                    ],
                ],
            ]);

        $storeResponse->assertCreated();
        $purchaseId = $storeResponse->json('data.id');

        $confirmResponse = $this->actingAs($this->user, 'api')->withHeaders($this->authHeaders())
            ->postJson("/api/purchases/{$purchaseId}/confirm");

        $confirmResponse->assertOk();

        $this->assertSame(OrderStatus::ToPack, $order->fresh()->status);
    }
}
