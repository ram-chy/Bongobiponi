<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    private function makeDeliveredOrder(Book $book, int $quantity, float $unitPrice): Order
    {
        $order = Order::factory()->create([
            'status' => 'delivered',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
            'grand_total' => $quantity * $unitPrice,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'book_id' => $book->id,
            'source_type' => 'manual',
            'item_no' => 1,
            'description' => $book->title,
            'unit' => 'pcs',
            'ordered_quantity' => $quantity,
            'remaining_order_quantity' => 0,
            'unit_price' => $unitPrice,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 0,
            'tax_amount' => 0,
            'line_total' => $quantity * $unitPrice,
            'sort_order' => 1,
        ]);

        return $order;
    }

    public function test_summary_requires_authentication(): void
    {
        $this->getJson('/api/dashboard/summary')->assertUnauthorized();
    }

    public function test_summary_reports_sales_value_and_profit(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id, 'purchase_price' => 50]);
        $this->makeDeliveredOrder($book, 10, 100);

        $response = $this->getJson('/api/dashboard/summary', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.summary.total_orders', 1)
            ->assertJsonPath('data.summary.sales_value', 1000)
            ->assertJsonPath('data.summary.profit', 500)
            ->assertJsonPath('data.summary.net_profit', 500)
            ->assertJsonCount(1, 'data.recent_orders')
            ->assertJsonCount(1, 'data.top_books');
    }

    public function test_summary_excludes_cancelled_and_rto_orders(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id, 'purchase_price' => 50]);
        $this->makeDeliveredOrder($book, 10, 100);

        Order::factory()->create([
            'status' => 'cancelled',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
            'grand_total' => 5000,
        ]);

        Order::factory()->create([
            'status' => 'rto',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
            'grand_total' => 5000,
        ]);

        $response = $this->getJson('/api/dashboard/summary', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.summary.total_orders', 3)
            ->assertJsonPath('data.summary.sales_value', 1000);
    }

    public function test_summary_includes_expenses_in_net_profit(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id, 'purchase_price' => 50]);
        $this->makeDeliveredOrder($book, 10, 100);

        Expense::factory()->create([
            'created_by' => $this->user->id,
            'expense_date' => now()->format('Y-m-d'),
            'amount' => 200,
        ]);

        $response = $this->getJson('/api/dashboard/summary', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.summary.expense_total', 200)
            ->assertJsonPath('data.summary.profit', 500)
            ->assertJsonPath('data.summary.net_profit', 300);
    }

    public function test_summary_reports_low_stock_count(): void
    {
        $book = Book::factory()->create([
            'created_by' => $this->user->id,
            'purchase_price' => 50,
            'minimum_stock' => 10,
        ]);

        \App\Models\Stock::create(['book_id' => $book->id, 'current_quantity' => 4]);

        $this->getJson('/api/dashboard/summary', $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data.summary.low_stock_count', 1);
    }

    public function test_regular_user_sees_only_own_data(): void
    {
        $book = Book::factory()->create(['created_by' => $this->user->id, 'purchase_price' => 50]);
        $this->makeDeliveredOrder($book, 10, 100);

        $otherUser = User::factory()->create(['role_id' => 3]);
        $otherToken = auth('api')->login($otherUser);

        $response = $this->getJson('/api/dashboard/summary', [
            'Authorization' => "Bearer {$otherToken}",
        ]);

        $response->assertOk()
            ->assertJsonPath('data.summary.total_orders', 0)
            ->assertJsonPath('data.summary.sales_value', 0);
    }
}
