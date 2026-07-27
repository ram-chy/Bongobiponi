<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
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

    public function test_cannot_create_sales_order_exceeding_order_remaining_quantity(): void
    {
        $orderItem = $this->createOrderItem($this->customer, $this->user, orderedQuantity: 5, remainingQuantity: 5);

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 6,
            ]],
        ], $this->authHeaders());

        $response->assertStatus(500);
        $orderItem->refresh();
        $this->assertEquals(5, (float) $orderItem->remaining_order_quantity);
    }

    public function test_sequential_sales_orders_correctly_decrement_order_quantity(): void
    {
        $orderItem = $this->createOrderItem($this->customer, $this->user, orderedQuantity: 10, remainingQuantity: 10);

        $response1 = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 4,
            ]],
        ], $this->authHeaders());
        $response1->assertCreated();

        $orderItem->refresh();
        $this->assertEquals(6, (float) $orderItem->remaining_order_quantity);

        $response2 = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 6,
            ]],
        ], $this->authHeaders());
        $response2->assertCreated();

        $orderItem->refresh();
        $this->assertEquals(0, (float) $orderItem->remaining_order_quantity);

        $response3 = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 1,
            ]],
        ], $this->authHeaders());
        $response3->assertStatus(500);

        $orderItem->refresh();
        $this->assertEquals(0, (float) $orderItem->remaining_order_quantity);
    }

    public function test_cannot_create_delivery_challan_exceeding_sales_order_remaining_quantity(): void
    {
        $salesOrderItem = $this->createSalesOrderItem($this->customer, $this->user, deliveredQuantity: 10, remainingQuantity: 5);

        $response = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->toDateString(),
            'delivery_address' => 'Test address',
            'items' => [[
                'sales_order_item_id' => $salesOrderItem->id,
                'delivered_quantity' => 6,
            ]],
        ], $this->authHeaders());

        $response->assertStatus(500);
    }

    public function test_cannot_create_invoice_exceeding_dc_item_remaining_quantity(): void
    {
        $dcItem = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);

        DB::table('delivery_challan_items')
            ->where('id', $dcItem->id)
            ->update(['invoiced_quantity' => 7, 'remaining_invoice_quantity' => 3]);

        $response = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 5,
            ]],
        ], $this->authHeaders());

        $response->assertStatus(500);
    }

    public function test_sequential_invoices_correctly_decrement_dc_remaining_quantity(): void
    {
        $dcItem = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);

        $response1 = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 3,
            ]],
        ], $this->authHeaders());
        $response1->assertCreated();

        $dcItem->refresh();
        $this->assertEquals(3, (float) $dcItem->invoiced_quantity);
        $this->assertEquals(7, (float) $dcItem->remaining_invoice_quantity);

        $response2 = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 7,
            ]],
        ], $this->authHeaders());
        $response2->assertCreated();

        $dcItem->refresh();
        $this->assertEquals(10, (float) $dcItem->invoiced_quantity);
        $this->assertEquals(0, (float) $dcItem->remaining_invoice_quantity);

        $response3 = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 1,
            ]],
        ], $this->authHeaders());
        $response3->assertStatus(500);

        $dcItem->refresh();
        $this->assertEquals(10, (float) $dcItem->invoiced_quantity);
        $this->assertEquals(0, (float) $dcItem->remaining_invoice_quantity);
    }

    public function test_payment_recalculate_locks_invoice_row(): void
    {
        $dcItem = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);

        $createResponse = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 10,
            ]],
        ], $this->authHeaders());
        $createResponse->assertCreated();
        $invoiceId = $createResponse->json('data.id');

        $response = $this->postJson('/api/payments', [
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => [[
                'invoice_id' => $invoiceId,
                'paid_amount' => 5,
            ]],
        ], $this->authHeaders());
        $response->assertCreated();

        $invoice = Invoice::find($invoiceId);
        $this->assertEquals(5, (float) $invoice->paid_amount);
        $this->assertEquals('Partially Paid', $invoice->payment_status);

        $response2 = $this->postJson('/api/payments', [
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => [[
                'invoice_id' => $invoiceId,
                'paid_amount' => 5,
            ]],
        ], $this->authHeaders());
        $response2->assertCreated();

        $invoice->refresh();
        $this->assertEquals(10, (float) $invoice->paid_amount);
        $this->assertEquals('Paid', $invoice->payment_status);
    }

    public function test_payment_restore_wrapped_in_transaction(): void
    {
        $dcItem = $this->createDeliveryChallanItem($this->customer, $this->user, deliveredQuantity: 10);

        $createResponse = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Test address',
            'items' => [[
                'delivery_challan_item_id' => $dcItem->id,
                'invoiced_quantity' => 10,
            ]],
        ], $this->authHeaders());
        $createResponse->assertCreated();
        $invoiceId = $createResponse->json('data.id');

        $payResponse = $this->postJson('/api/payments', [
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => [[
                'invoice_id' => $invoiceId,
                'paid_amount' => 10,
            ]],
        ], $this->authHeaders());
        $payResponse->assertCreated();
        $paymentId = $payResponse->json('data.id');

        $invoice = Invoice::find($invoiceId);
        $this->assertEquals('Paid', $invoice->payment_status);

        $deleteResponse = $this->deleteJson("/api/payments/{$paymentId}", [], $this->authHeaders());
        $deleteResponse->assertOk();

        $invoice->refresh();
        $this->assertEquals('Unpaid', $invoice->payment_status);
        $this->assertEquals(0, (float) $invoice->paid_amount);

        $restoreResponse = $this->postJson("/api/payments/{$paymentId}/restore", [], $this->authHeaders());
        $restoreResponse->assertOk();

        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->payment_status);
        $this->assertEquals(10, (float) $invoice->paid_amount);
    }

    public function test_dc_update_restores_and_reapplies_sales_order_quantities_atomically(): void
    {
        $salesOrderItem = $this->createSalesOrderItem($this->customer, $this->user, deliveredQuantity: 10, remainingQuantity: 10);
        $serial = 'BBDC/CONC1/'.now()->format('y').'/'.uniqid();

        $deliveryChallan = DeliveryChallan::create([
            'serial' => $serial,
            'delivery_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'delivery_address' => 'Test address',
            'subtotal' => 5,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 5,
            'created_by' => $this->user->id,
        ]);

        $dcItem = DeliveryChallanItem::create([
            'delivery_challan_id' => $deliveryChallan->id,
            'sales_order_id' => $salesOrderItem->sales_order_id,
            'sales_order_item_id' => $salesOrderItem->id,
            'order_booking_id' => $salesOrderItem->order_id,
            'order_booking_item_id' => $salesOrderItem->order_item_id,
            'item_description' => 'Test',
            'unit' => 'pcs',
            'ordered_quantity' => 10,
            'delivered_quantity' => 5,
            'unit_price' => 1,
        ]);

        DB::table('delivery_challan_items')
            ->where('id', $dcItem->id)
            ->update(['remaining_invoice_quantity' => 5]);

        $salesOrderItem->refresh();
        $this->assertEquals(10, (float) $salesOrderItem->remaining_sales_quantity);

        $updateResponse = $this->putJson("/api/delivery-challans/{$deliveryChallan->id}", [
            'items' => [[
                'sales_order_item_id' => $salesOrderItem->id,
                'delivered_quantity' => 3,
            ]],
        ], $this->authHeaders());
        $updateResponse->assertOk();

        $salesOrderItem->refresh();
        $this->assertEquals(12, (float) $salesOrderItem->remaining_sales_quantity);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($this->user)];
    }

    private function createOrderItem(
        Customer $customer,
        User $user,
        float $orderedQuantity,
        float $remainingQuantity,
    ): OrderItem {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'ordered_quantity' => $orderedQuantity,
            'remaining_order_quantity' => $remainingQuantity,
        ]);
    }

    private function createSalesOrderItem(
        Customer $customer,
        User $user,
        float $deliveredQuantity,
        float $remainingQuantity,
    ): SalesOrderItem {
        $orderItem = $this->createOrderItem($customer, $user, orderedQuantity: $deliveredQuantity, remainingQuantity: $remainingQuantity);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        return SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'remaining_sales_quantity' => $remainingQuantity,
        ]);
    }

    private function createDeliveryChallanItem(
        Customer $customer,
        User $user,
        float $deliveredQuantity,
    ): DeliveryChallanItem {
        $salesOrderItem = $this->createSalesOrderItem($customer, $user, deliveredQuantity: $deliveredQuantity, remainingQuantity: 100);
        $serial = 'BBDC/CONCTEST/'.now()->format('y').'/'.uniqid();

        $deliveryChallan = DeliveryChallan::create([
            'serial' => $serial,
            'delivery_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'delivery_address' => 'Test address',
            'subtotal' => $deliveredQuantity,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => $deliveredQuantity,
            'created_by' => $user->id,
        ]);

        $dcItem = DeliveryChallanItem::create([
            'delivery_challan_id' => $deliveryChallan->id,
            'sales_order_id' => $salesOrderItem->sales_order_id,
            'sales_order_item_id' => $salesOrderItem->id,
            'order_booking_id' => $salesOrderItem->order_id,
            'order_booking_item_id' => $salesOrderItem->order_item_id,
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
