<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private Order $order;

    private OrderItem $orderItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);

        $this->order = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'ordered_quantity' => 100,
            'remaining_order_quantity' => 100,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_percentage' => 18,
        ]);
    }

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function validPayload(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(7)->format('Y-m-d'),
            'notes' => 'Test sales order',
            'items' => [
                [
                    'order_item_id' => $this->orderItem->id,
                    'sales_order_quantity' => 10,
                ],
            ],
        ];
    }

    public function test_can_create_sales_order(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Sales Order created successfully',
            ]);

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('sales_order_items', [
            'order_item_id' => $this->orderItem->id,
            'sales_order_quantity' => 10,
        ]);
    }

    public function test_sales_order_serial_is_auto_generated(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('BBSO/', $response->json('data.sales_order_serial'));
        $this->assertStringEndsWith('/' . now()->format('y'), $response->json('data.sales_order_serial'));
    }

    public function test_created_by_is_set_automatically(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals($this->user->id, $response->json('data.created_by.id'));
    }

    public function test_document_reference_uuid_is_generated(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertNotNull($response->json('data.document_reference_uuid'));
    }

    public function test_totals_are_calculated_automatically(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();

        $data = $response->json('data');

        // qty=10, price=100, disc=0%, tax=18%
        // base=1000, disc=0, taxable=1000, tax=180, line_total=1180
        $this->assertEquals(1000, (int) $data['subtotal']);
        $this->assertEquals(0, (int) $data['discount_amount']);
        $this->assertEquals(180, (int) $data['tax_amount']);
        $this->assertEquals(1180, (int) $data['grand_total']);
    }

    public function test_unauthenticated_user_cannot_create_sales_order(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload());

        $response->assertUnauthorized();
    }

    public function test_can_list_sales_orders_with_pagination(): void
    {
        SalesOrder::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/sales-orders', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_sales_orders(): void
    {
        SalesOrder::factory()->create([
            'sales_order_serial' => 'BBSO/999/26',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        SalesOrder::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/sales-orders?search=999', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status(): void
    {
        SalesOrder::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        SalesOrder::factory()->create([
            'status' => 'confirmed',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/sales-orders?status=draft', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_sales_order(): void
    {
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/sales-orders/{$salesOrder->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $salesOrder->id);
    }

    public function test_can_update_sales_order(): void
    {
        $this->user->update(['role_id' => 1]);
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->putJson("/api/sales-orders/{$salesOrder->id}", [
            'notes' => 'Updated notes',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_can_soft_delete_sales_order(): void
    {
        $this->user->update(['role_id' => 1]);
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->deleteJson("/api/sales-orders/{$salesOrder->id}", [], $this->authHeaders());

        $response->assertOk();
        $this->assertSoftDeleted($salesOrder);
    }

    public function test_can_restore_sales_order(): void
    {
        $this->user->update(['role_id' => 1]);
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $salesOrder->delete();

        $response = $this->postJson("/api/sales-orders/{$salesOrder->id}/restore", [], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('message', 'Sales Order restored successfully');
        $this->assertNotSoftDeleted($salesOrder);
    }

    public function test_sales_order_must_have_at_least_one_item(): void
    {
        $payload = $this->validPayload();
        $payload['items'] = [];

        $response = $this->postJson('/api/sales-orders', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_sales_order_quantity_deducts_from_order_item(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();

        $this->orderItem->refresh();
        $this->assertEquals(90, (int) $this->orderItem->remaining_order_quantity);
    }

    public function test_cannot_exceed_available_quantity(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['sales_order_quantity'] = 200;

        $response = $this->postJson('/api/sales-orders', $payload, $this->authHeaders());

        $response->assertStatus(500);
    }

    public function test_can_create_sales_order_with_multiple_items(): void
    {
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'remaining_order_quantity' => 50,
            'unit_price' => 200,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->format('Y-m-d'),
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'sales_order_quantity' => 10],
                ['order_item_id' => $orderItem2->id, 'sales_order_quantity' => 5],
            ],
        ];

        $response = $this->postJson('/api/sales-orders', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_can_create_sales_order_from_multiple_orders(): void
    {
        $order2 = Order::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $order2->id,
            'remaining_order_quantity' => 30,
            'unit_price' => 300,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->format('Y-m-d'),
            'items' => [
                ['order_item_id' => $this->orderItem->id, 'sales_order_quantity' => 5],
                ['order_item_id' => $orderItem2->id, 'sales_order_quantity' => 10],
            ],
        ];

        $response = $this->postJson('/api/sales-orders', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('merged', $response->json('data.sales_order_source'));
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_serial_resets_yearly(): void
    {
        SalesOrder::factory()->create([
            'sales_order_serial' => 'BBSO/005/' . now()->format('y'),
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('BBSO/006/' . now()->format('y'), $response->json('data.sales_order_serial'));
    }

    public function test_can_download_pdf(): void
    {
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/sales-orders/{$salesOrder->id}/download-pdf", $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }

    public function test_document_reference_created_for_parent_orders(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();

        $uuid = $response->json('data.document_reference_uuid');

        $this->assertDatabaseHas('document_references', [
            'uuid' => $uuid,
            'document_type' => 'sales_order',
            'parent_document_type' => 'order',
            'parent_document_id' => $this->order->id,
        ]);
    }

    public function test_activity_log_created_on_store(): void
    {
        $response = $this->postJson('/api/sales-orders', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();

        $salesOrderId = $response->json('data.id');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'module' => 'sales_order',
            'document_type' => 'sales_order',
            'document_id' => $salesOrderId,
            'action' => 'created',
        ]);
    }

    public function test_activity_log_created_on_delete(): void
    {
        $this->user->update(['role_id' => 1]);
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->deleteJson("/api/sales-orders/{$salesOrder->id}", [], $this->authHeaders());

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'sales_order',
            'document_type' => 'sales_order',
            'document_id' => $salesOrder->id,
            'action' => 'deleted',
        ]);
    }
}
