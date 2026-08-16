<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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
                [
                    'description' => 'Service B',
                    'unit' => 'hours',
                    'ordered_quantity' => 2,
                    'unit_price' => 500,
                    'discount_percentage' => 0,
                    'tax_percentage' => 18,
                ],
            ],
        ];
    }

    public function test_can_create_manual_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Order created successfully',
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'order_source' => 'manual',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseCount('order_items', 2);
    }

    public function test_new_order_starts_in_intake_status(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();

        $this->assertSame('intake', $response->json('data.status'));

        $this->assertDatabaseHas('orders', [
            'order_serial' => $response->json('data.order_serial'),
            'status' => 'intake',
        ]);
    }

    public function test_order_serial_is_auto_generated(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('BB/', $response->json('data.order_serial'));
        $this->assertStringEndsWith('/' . now()->format('y'), $response->json('data.order_serial'));
    }

    public function test_created_by_is_set_automatically(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals($this->user->id, $response->json('data.created_by.id'));
    }

    public function test_totals_are_calculated_automatically_for_manual_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();

        $data = $response->json('data');

        // Item 1: qty=10, price=100, disc=10%, tax=18%
        // base = 1000, disc = 100, taxable = 900, tax = 162, line_total = 1062
        // Item 2: qty=2, price=500, disc=0%, tax=18%
        // base = 1000, disc = 0, taxable = 1000, tax = 180, line_total = 1180
        // subtotal = 2000, discount = 100, tax = 342, grand_total = 2242

        $this->assertEquals(2000, (int) $data['subtotal']);
        $this->assertEquals(100, (int) $data['discount_amount']);
        $this->assertEquals(342, (int) $data['tax_amount']);
        $this->assertEquals(2242, (int) $data['grand_total']);
    }

    public function test_unauthenticated_user_cannot_create_order(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload());

        $response->assertUnauthorized();
    }

    public function test_can_list_orders_with_pagination(): void
    {
        Order::factory()->count(5)->create(['created_by' => $this->user->id, 'customer_id' => $this->customer->id]);

        $response = $this->getJson('/api/orders', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_orders(): void
    {
        Order::factory()->create([
            'order_serial' => 'BB/999/26',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Order::factory()->count(3)->create(['created_by' => $this->user->id, 'customer_id' => $this->customer->id]);

        $response = $this->getJson('/api/orders?search=999', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status(): void
    {
        Order::factory()->create([
            'status' => 'intake',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Order::factory()->create([
            'status' => 'to_pack',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/orders?status=intake', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_can_update_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'notes' => 'Updated notes',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_can_soft_delete_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders());

        $response->assertOk();
        $this->assertSoftDeleted($order);
    }

    public function test_can_restore_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $order->delete();

        $response = $this->postJson("/api/orders/{$order->id}/restore", [], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('message', 'Order restored successfully');
        $this->assertNotSoftDeleted($order);
    }

    public function test_order_must_have_at_least_one_item(): void
    {
        $payload = $this->validManualPayload();
        $payload['items'] = [];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_manual_items_require_description_unit_and_unit_price(): void
    {
        $payload = $this->validManualPayload();
        $payload['items'] = [
            ['ordered_quantity' => 5],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_serial_resets_yearly(): void
    {
        Order::factory()->create([
            'order_serial' => 'BB/005/' . now()->format('y'),
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('BB/006/' . now()->format('y'), $response->json('data.order_serial'));
    }

    public function test_can_download_pdf(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}/download-pdf", $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }

    public function test_order_completes_when_fully_delivered_via_delivery_challan(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

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

        $response = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->format('Y-m-d'),
            'delivery_address' => 'Test Address',
            'items' => [
                [
                    'order_booking_item_id' => $orderItem->id,
                    'delivered_quantity' => 10,
                ],
            ],
        ], $this->authHeaders());

        $response->assertCreated();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'remaining_order_quantity' => 0,
        ]);
    }

    public function test_order_reverts_to_draft_when_delivery_challan_is_deleted(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

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

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'delivered',
        ]);

        $this->deleteJson("/api/delivery-challans/{$deliveryChallanId}", [], $this->authHeaders())
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'intake',
        ]);

        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'remaining_order_quantity' => 10,
        ]);
    }

    public function test_manual_order_persists_book_id_on_items(): void
    {
        $book = \App\Models\Book::factory()->create();

        $payload = $this->validManualPayload();
        $payload['items'][0]['book_id'] = $book->id;

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();

        $this->assertDatabaseHas('order_items', [
            'order_id' => $response->json('data.id'),
            'book_id' => $book->id,
        ]);

        $this->assertEquals(
            $book->id,
            $response->json('data.items.0.book_id')
        );
    }

    public function test_order_requires_valid_book_id_when_provided(): void
    {
        $payload = $this->validManualPayload();
        $payload['items'][0]['book_id'] = 999999;

        $this->postJson('/api/orders', $payload, $this->authHeaders())
            ->assertJsonValidationErrors(['items.0.book_id']);
    }

    public function test_manual_order_defaults_pre_book_to_false(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();

        $this->assertFalse($response->json('data.pre_book'));
    }

    public function test_manual_order_persists_pre_book_flag(): void
    {
        $payload = $this->validManualPayload();
        $payload['pre_book'] = true;

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertTrue($response->json('data.pre_book'));

        $this->assertDatabaseHas('orders', [
            'id' => $response->json('data.id'),
            'pre_book' => true,
        ]);
    }

}
