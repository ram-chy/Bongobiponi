<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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

    private function createDeliveredOrderWithChallan(): array
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

        $deliveryChallan = $this->postJson('/api/delivery-challans', [
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

        return [
            'deliveryChallan' => $deliveryChallan,
            'orderItem' => $orderItem,
        ];
    }

    public function test_can_list_invoiceable_items_for_delivery_challan(): void
    {
        $setup = $this->createDeliveredOrderWithChallan();
        $dcItemId = $setup['deliveryChallan']->json('data.items.0.id');

        $this->getJson("/api/delivery-challans/{$setup['deliveryChallan']->json('data.id')}/invoiceable-items", $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_can_create_invoice_from_delivery_challan_items(): void
    {
        $setup = $this->createDeliveredOrderWithChallan();
        $dcItemId = $setup['deliveryChallan']->json('data.items.0.id');

        $response = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'billing_address' => 'Test Address',
            'items' => [
                [
                    'delivery_challan_item_id' => $dcItemId,
                    'invoiced_quantity' => 10,
                    'unit_price' => 100,
                ],
            ],
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Invoice created successfully',
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'grand_total' => 1000.00,
            'payment_status' => 'Unpaid',
        ]);

        $this->assertDatabaseHas('invoice_items', [
            'delivery_challan_item_id' => $dcItemId,
            'invoiced_quantity' => 10,
            'line_total' => 1000.00,
        ]);
    }

    public function test_can_show_invoice_after_creation(): void
    {
        $setup = $this->createDeliveredOrderWithChallan();
        $dcItemId = $setup['deliveryChallan']->json('data.items.0.id');

        $invoice = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'billing_address' => 'Test Address',
            'items' => [
                [
                    'delivery_challan_item_id' => $dcItemId,
                    'invoiced_quantity' => 10,
                    'unit_price' => 100,
                ],
            ],
        ], $this->authHeaders());

        $this->getJson("/api/invoices/{$invoice->json('data.id')}", $this->authHeaders())
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'serial', 'items'],
                'message',
            ]);
    }
}
