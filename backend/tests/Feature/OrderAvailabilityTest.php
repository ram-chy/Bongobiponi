<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\User;
use App\Services\OrderAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAvailabilityTest extends TestCase
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

    private function makeOrder(array $items): Order
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
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

    private function check(Order $order): array
    {
        return app(OrderAvailabilityService::class)->check($order);
    }

    public function test_fully_available_when_stock_equals_requirement(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 5);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $result = $this->check($order);

        $this->assertSame('fully_available', $result['status']);
        $this->assertTrue($result['fully_available']);
        $this->assertTrue($result['items'][0]['is_available']);
        $this->assertEquals(0, $result['items'][0]['shortage_quantity']);
        $this->assertEquals(5, $result['items'][0]['available_quantity']);
    }

    public function test_fully_available_when_stock_exceeds_requirement(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 10);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $result = $this->check($order);

        $this->assertSame('fully_available', $result['status']);
        $this->assertTrue($result['items'][0]['is_available']);
        $this->assertEquals(0, $result['items'][0]['shortage_quantity']);
    }

    public function test_partially_available_with_shortage(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 6);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 10]]);

        $result = $this->check($order);

        $this->assertSame('partially_available', $result['status']);
        $this->assertFalse($result['fully_available']);
        $this->assertFalse($result['items'][0]['is_available']);
        $this->assertEquals(4, $result['items'][0]['shortage_quantity']);
        $this->assertEquals(6, $result['items'][0]['available_quantity']);
    }

    public function test_unavailable_when_zero_stock(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 0);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $result = $this->check($order);

        $this->assertSame('unavailable', $result['status']);
        $this->assertFalse($result['fully_available']);
        $this->assertFalse($result['items'][0]['is_available']);
        $this->assertEquals(5, $result['items'][0]['shortage_quantity']);
    }

    public function test_missing_inventory_record_means_zero_stock(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $result = $this->check($order);

        $this->assertSame('unavailable', $result['status']);
        $this->assertEquals(0, $result['items'][0]['available_quantity']);
        $this->assertEquals(5, $result['items'][0]['shortage_quantity']);
        $this->assertFalse($result['items'][0]['is_available']);
    }

    public function test_multi_item_fully_available(): void
    {
        $bookA = Book::factory()->create(['created_by' => $this->user->id]);
        $bookB = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($bookA, 5);
        $this->setStock($bookB, 2);
        $order = $this->makeOrder([
            ['book_id' => $bookA->id, 'quantity' => 5],
            ['book_id' => $bookB->id, 'quantity' => 2],
        ]);

        $result = $this->check($order);

        $this->assertSame('fully_available', $result['status']);
        $this->assertTrue($result['fully_available']);
    }

    public function test_multi_item_partially_available(): void
    {
        $bookA = Book::factory()->create(['created_by' => $this->user->id]);
        $bookB = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($bookA, 5);
        $this->setStock($bookB, 1);
        $order = $this->makeOrder([
            ['book_id' => $bookA->id, 'quantity' => 5],
            ['book_id' => $bookB->id, 'quantity' => 2],
        ]);

        $result = $this->check($order);

        $this->assertSame('partially_available', $result['status']);
        $this->assertFalse($result['fully_available']);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(1, $result['items'][1]['shortage_quantity']);
    }

    public function test_duplicate_book_items_are_aggregated(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 6);
        $order = $this->makeOrder([
            ['book_id' => $book->id, 'quantity' => 3],
            ['book_id' => $book->id, 'quantity' => 4],
        ]);

        $result = $this->check($order);

        $this->assertCount(1, $result['items']);
        $this->assertEquals(7, $result['items'][0]['required_quantity']);
        $this->assertEquals(1, $result['items'][0]['shortage_quantity']);
        $this->assertFalse($result['items'][0]['is_available']);
        $this->assertSame('partially_available', $result['status']);
    }

    public function test_missing_book_reference_is_unverifiable(): void
    {
        $order = $this->makeOrder([['book_id' => null, 'quantity' => 3]]);

        $result = $this->check($order);

        $this->assertSame('unverifiable', $result['status']);
        $this->assertFalse($result['fully_available']);
        $this->assertTrue($result['items'][0]['unverifiable']);
        $this->assertFalse($result['items'][0]['is_available']);
    }

    public function test_orphaned_book_reference_is_unverifiable(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 10);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $book->delete();

        $result = $this->check($order);

        $this->assertSame('unverifiable', $result['status']);
        $this->assertFalse($result['fully_available']);
        $this->assertTrue($result['items'][0]['unverifiable']);
    }

    public function test_availability_reflects_inventory_changes(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 2);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $this->assertSame('partially_available', $this->check($order)['status']);

        Stock::where('book_id', $book->id)->update(['current_quantity' => 5]);

        $this->assertSame('fully_available', $this->check($order)['status']);
    }

    public function test_check_does_not_mutate_inventory_or_order(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 4);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $stockBefore = Stock::where('book_id', $book->id)->value('current_quantity');
        $transactionCountBefore = InventoryTransaction::count();
        $orderSnapshot = $order->fresh();
        $itemsBefore = $order->items()->pluck('remaining_order_quantity')->all();

        $this->check($order);

        $this->assertSame($stockBefore, Stock::where('book_id', $book->id)->value('current_quantity'));
        $this->assertSame($transactionCountBefore, InventoryTransaction::count());
        $this->assertSame($itemsBefore, $order->items()->pluck('remaining_order_quantity')->all());
        $this->assertEquals($orderSnapshot->grand_total, $order->fresh()->grand_total);
    }

    public function test_check_does_not_change_order_status(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 5);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $this->assertSame('intake', $order->fresh()->status->value);

        $this->check($order);

        $this->assertSame('intake', $order->fresh()->status->value);
    }

    public function test_api_returns_fully_available_order(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 5);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $response = $this->getJson("/api/orders/{$order->id}/availability", $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_id' => $order->id,
                    'status' => 'fully_available',
                    'fully_available' => true,
                ],
            ]);
    }

    public function test_api_returns_partially_available_with_shortage_details(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 6);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 10]]);

        $response = $this->getJson("/api/orders/{$order->id}/availability", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'partially_available')
            ->assertJsonPath('data.fully_available', false);

        $this->assertEquals(4, $response->json('data.items.0.shortage_quantity'));
        $this->assertFalse($response->json('data.items.0.is_available'));
    }

    public function test_api_reports_missing_book_reference(): void
    {
        $order = $this->makeOrder([['book_id' => null, 'quantity' => 3]]);

        $response = $this->getJson("/api/orders/{$order->id}/availability", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.status', 'unverifiable')
            ->assertJsonPath('data.fully_available', false)
            ->assertJsonPath('data.items.0.unverifiable', true);
    }

    public function test_api_requires_authentication(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 5);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $response = $this->getJson("/api/orders/{$order->id}/availability");

        $response->assertUnauthorized();
    }

    public function test_api_returns_404_for_non_existent_order(): void
    {
        $response = $this->getJson('/api/orders/999999/availability', $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_api_regular_user_cannot_view_other_users_order(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id]);
        $this->setStock($book, 5);
        $order = $this->makeOrder([['book_id' => $book->id, 'quantity' => 5]]);

        $otherUser = User::factory()->create(['role_id' => 3]);
        $token = auth('api')->login($otherUser);

        $response = $this->getJson("/api/orders/{$order->id}/availability", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertNotFound();
    }
}
