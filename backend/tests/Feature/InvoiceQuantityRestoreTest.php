<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvoiceQuantityRestoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin'],
        );

        $this->user = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_updating_invoice_items_restores_dc_quantities_exactly_once(): void
    {
        $dcItem = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);

        $this->assertEquals(0, (float) $dcItem->fresh()->invoiced_quantity);
        $this->assertEquals(10, (float) $dcItem->fresh()->remaining_invoice_quantity);

        $createResponse = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 3,
            ]],
        ], $this->authHeaders());

        $createResponse->assertCreated();
        $invoiceId = $createResponse->json('data.id');

        $dcItem->refresh();
        $this->assertEquals(3, (float) $dcItem->invoiced_quantity);
        $this->assertEquals(7, (float) $dcItem->remaining_invoice_quantity);

        $updateResponse = $this->putJson("/api/invoices/{$invoiceId}", [
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 5,
            ]],
        ], $this->authHeaders());

        $updateResponse->assertOk();

        $dcItem->refresh();
        $this->assertEquals(5, (float) $dcItem->invoiced_quantity);
        $this->assertEquals(5, (float) $dcItem->remaining_invoice_quantity);
    }

    public function test_updating_invoice_to_different_dc_item_restores_old_quantities_correctly(): void
    {
        $dcItem1 = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);
        $dcItem2 = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 8);

        $createResponse = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem1->id,
                'invoiced_quantity' => 4,
            ]],
        ], $this->authHeaders());

        $createResponse->assertCreated();
        $invoiceId = $createResponse->json('data.id');

        $dcItem1->refresh();
        $this->assertEquals(4, (float) $dcItem1->invoiced_quantity);
        $this->assertEquals(6, (float) $dcItem1->remaining_invoice_quantity);

        $updateResponse = $this->putJson("/api/invoices/{$invoiceId}", [
            'items' => [[
                'delivery_challan_item_id' => $dcItem2->id,
                'invoiced_quantity' => 5,
            ]],
        ], $this->authHeaders());

        $updateResponse->assertOk();

        $dcItem1->refresh();
        $this->assertEquals(0, (float) $dcItem1->invoiced_quantity);
        $this->assertEquals(10, (float) $dcItem1->remaining_invoice_quantity);

        $dcItem2->refresh();
        $this->assertEquals(5, (float) $dcItem2->invoiced_quantity);
        $this->assertEquals(3, (float) $dcItem2->remaining_invoice_quantity);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($this->user)];
    }

    private function createDeliveryChallanItem(
        Customer $customer,
        User $user,
        float $deliveredQuantity,
    ): DeliveryChallanItem {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'remaining_order_quantity' => 100,
        ]);

        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);
        $salesOrderItem = SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'remaining_sales_quantity' => 100,
        ]);

        $serial = 'BBDC/TQRTEST/'.now()->format('y').'/'.uniqid();
        $deliveryChallan = DeliveryChallan::create([
            'serial' => $serial,
            'delivery_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'delivery_address' => 'Test delivery address',
            'subtotal' => $deliveredQuantity,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => $deliveredQuantity,
            'created_by' => $user->id,
        ]);

        $dcItem = DeliveryChallanItem::create([
            'delivery_challan_id' => $deliveryChallan->id,
            'sales_order_id' => $salesOrder->id,
            'sales_order_item_id' => $salesOrderItem->id,
            'order_booking_id' => $order->id,
            'order_booking_item_id' => $orderItem->id,
            'item_description' => 'Test item',
            'unit' => 'pcs',
            'ordered_quantity' => $deliveredQuantity,
            'delivered_quantity' => $deliveredQuantity,
            'unit_price' => 1,
        ]);

        DB::table('delivery_challan_items')
            ->where('id', $dcItem->id)
            ->update([
                'invoiced_quantity' => 0,
                'remaining_invoice_quantity' => $deliveredQuantity,
            ]);

        return $dcItem->fresh();
    }
}
