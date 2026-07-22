<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use App\Models\QuotationItem;
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

        $this->user = User::factory()->create();
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

    public function test_order_serial_is_auto_generated(): void
    {
        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('GG/', $response->json('data.order_serial'));
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
            'order_serial' => 'GG/999/26',
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
            'status' => 'draft',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Order::factory()->create([
            'status' => 'confirmed',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/orders?status=draft', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_order_source(): void
    {
        Order::factory()->create([
            'order_source' => 'manual',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Order::factory()->create([
            'order_source' => 'quotation',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/orders?order_source=manual', $this->authHeaders());

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
        $this->user->update(['role_id' => 1]);
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
        $this->user->update(['role_id' => 1]);
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
        $this->user->update(['role_id' => 1]);
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

    public function test_can_create_order_from_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $quotationItem = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'description' => 'Quoted Product',
            'unit' => 'pcs',
            'quantity' => 50,
            'unit_price' => 200,
            'discount_percentage' => 5,
            'tax_percentage' => 18,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'quotation_item_id' => $quotationItem->id,
                    'ordered_quantity' => 10,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();

        $data = $response->json('data');
        $this->assertEquals('quotation', $data['order_source']);

        $itemData = $data['items'][0];
        $this->assertEquals('quotation', $itemData['source_type']);
        $this->assertEquals('Quoted Product', $itemData['description']);
        $this->assertEquals(200, (int) $itemData['unit_price']);
        $this->assertEquals(50, (int) $itemData['quoted_quantity']);
        $this->assertEquals(10, (int) $itemData['ordered_quantity']);
    }

    public function test_can_create_mixed_order(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $quotationItem = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'description' => 'Quoted Item',
            'unit' => 'pcs',
            'quantity' => 20,
            'unit_price' => 150,
            'discount_percentage' => 0,
            'tax_percentage' => 18,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'quotation_item_id' => $quotationItem->id,
                    'ordered_quantity' => 5,
                ],
                [
                    'description' => 'Manual Extra Item',
                    'unit' => 'job',
                    'ordered_quantity' => 1,
                    'unit_price' => 3000,
                    'discount_percentage' => 0,
                    'tax_percentage' => 18,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();

        $data = $response->json('data');
        $this->assertEquals('mixed', $data['order_source']);
        $this->assertCount(2, $data['items']);
    }

    public function test_can_create_order_with_items_from_multiple_quotations(): void
    {
        $quotationA = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $quotationB = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $itemA = QuotationItem::factory()->create([
            'quotation_id' => $quotationA->id,
            'description' => 'Item from Quotation A',
            'unit' => 'pcs',
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $itemB = QuotationItem::factory()->create([
            'quotation_id' => $quotationB->id,
            'description' => 'Item from Quotation B',
            'unit' => 'pcs',
            'quantity' => 20,
            'unit_price' => 200,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                ['quotation_item_id' => $itemA->id, 'ordered_quantity' => 5],
                ['quotation_item_id' => $itemB->id, 'ordered_quantity' => 10],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('quotation', $response->json('data.order_source'));
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_serial_resets_yearly(): void
    {
        Order::factory()->create([
            'order_serial' => 'GG/005/' . now()->format('y'),
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->postJson('/api/orders', $this->validManualPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('GG/006/' . now()->format('y'), $response->json('data.order_serial'));
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

    public function test_ordered_quantity_may_exceed_quoted_quantity(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $quotationItem = QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'quantity' => 10,
            'unit_price' => 100,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'quotation_item_id' => $quotationItem->id,
                    'ordered_quantity' => 25,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals(25, (int) $response->json('data.items.0.ordered_quantity'));
        $this->assertEquals(10, (int) $response->json('data.items.0.quoted_quantity'));
    }
}
