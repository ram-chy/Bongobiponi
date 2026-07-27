<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\DeliveryChallanItem;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SourceDocumentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private User $otherUser;
    private Customer $otherCustomer;
    private Customer $alternateCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $regularUserRole = Role::firstOrCreate([
            'slug' => 'regular_user',
        ], [
            'name' => 'Regular User',
        ]);

        $this->user = User::factory()->create(['role_id' => $regularUserRole->id]);
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);
        $this->otherUser = User::factory()->create(['role_id' => $regularUserRole->id]);
        $this->otherCustomer = Customer::factory()->create(['created_by' => $this->otherUser->id]);
        $this->alternateCustomer = Customer::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_regular_user_cannot_create_order_from_another_users_quotation_item(): void
    {
        $quotation = Quotation::factory()->create([
            'customer_id' => $this->otherCustomer->id,
            'created_by' => $this->otherUser->id,
        ]);
        $quotationItem = QuotationItem::factory()->create(['quotation_id' => $quotation->id]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'items' => [[
                'quotation_item_id' => $quotationItem->id,
                'ordered_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_create_sales_order_from_another_users_order_item(): void
    {
        $orderItem = $this->createOtherUsersOrderItem();

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertForbidden();
    }

    public function test_order_rejects_a_quotation_item_from_a_different_customer(): void
    {
        $quotation = Quotation::factory()->create([
            'customer_id' => $this->alternateCustomer->id,
            'created_by' => $this->user->id,
        ]);
        $quotationItem = QuotationItem::factory()->create(['quotation_id' => $quotation->id]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => $this->customer->id,
            'order_date' => now()->toDateString(),
            'items' => [[
                'quotation_item_id' => $quotationItem->id,
                'ordered_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_sales_order_rejects_an_order_item_from_a_different_customer(): void
    {
        $orderItem = $this->createOrderItem($this->alternateCustomer, $this->user);

        $response = $this->postJson('/api/sales-orders', [
            'customer_id' => $this->customer->id,
            'sales_order_date' => now()->toDateString(),
            'items' => [[
                'order_item_id' => $orderItem->id,
                'sales_order_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_regular_user_cannot_create_delivery_challan_from_another_users_sales_order_item(): void
    {
        $salesOrderItem = $this->createOtherUsersSalesOrderItem();

        $response = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->toDateString(),
            'delivery_address' => 'Delivery address',
            'items' => [[
                'sales_order_item_id' => $salesOrderItem->id,
                'delivered_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_create_invoice_from_another_users_delivery_challan_item(): void
    {
        $deliveryChallanItem = $this->createOtherUsersDeliveryChallanItem();

        $response = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Billing address',
            'items' => [[
                'delivery_challan_item_id' => $deliveryChallanItem->id,
                'invoiced_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertForbidden();
    }

    public function test_delivery_challan_rejects_a_sales_order_item_from_a_different_customer(): void
    {
        $salesOrderItem = $this->createSalesOrderItem($this->alternateCustomer, $this->user);

        $response = $this->postJson('/api/delivery-challans', [
            'customer_id' => $this->customer->id,
            'delivery_date' => now()->toDateString(),
            'delivery_address' => 'Delivery address',
            'items' => [[
                'sales_order_item_id' => $salesOrderItem->id,
                'delivered_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_invoice_rejects_a_delivery_challan_item_from_a_different_customer(): void
    {
        $deliveryChallanItem = $this->createDeliveryChallanItem($this->alternateCustomer, $this->user);

        $response = $this->postJson('/api/invoices', [
            'customer_id' => $this->customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'billing_address' => 'Billing address',
            'items' => [[
                'delivery_challan_item_id' => $deliveryChallanItem->id,
                'invoiced_quantity' => 1,
            ]],
        ], $this->authHeaders());

        $response->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    }

    public function test_regular_user_cannot_view_another_users_invoiceable_delivery_challan_items(): void
    {
        $deliveryChallanItem = $this->createOtherUsersDeliveryChallanItem();

        $response = $this->getJson(
            "/api/delivery-challans/{$deliveryChallanItem->delivery_challan_id}/invoiceable-items",
            $this->authHeaders(),
        );

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_create_payment_for_another_users_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->otherCustomer->id,
            'created_by' => $this->otherUser->id,
            'grand_total' => 100,
            'payment_status' => 'Unpaid',
        ]);

        $response = $this->postJson('/api/payments', [
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => [[
                'invoice_id' => $invoice->id,
                'paid_amount' => 10,
            ]],
        ], $this->authHeaders());

        $response->assertForbidden();
    }

    public function test_payment_rejects_an_invoice_from_a_different_customer(): void
    {
        $invoice = Invoice::factory()->create([
            'customer_id' => $this->alternateCustomer->id,
            'created_by' => $this->user->id,
            'grand_total' => 100,
            'payment_status' => 'Unpaid',
        ]);

        $response = $this->postJson('/api/payments', [
            'customer_id' => $this->customer->id,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Cash',
            'items' => [[
                'invoice_id' => $invoice->id,
                'paid_amount' => 10,
            ]],
        ], $this->authHeaders());

        $response->assertUnprocessable()->assertJsonValidationErrors('items.0.invoice_id');
    }

    // --- Ownership validation: view (show) ---

    public function test_regular_user_cannot_view_another_users_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/quotations/{$quotation->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_regular_user_cannot_view_another_users_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_regular_user_cannot_view_another_users_sales_order(): void
    {
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/sales-orders/{$salesOrder->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_regular_user_cannot_view_another_users_delivery_challan(): void
    {
        $deliveryChallan = DeliveryChallan::create([
            'serial' => 'BBDC/998/' . now()->format('y'),
            'delivery_date' => now()->toDateString(),
            'customer_id' => $this->otherCustomer->id,
            'delivery_address' => 'Address',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
            'created_by' => $this->otherUser->id,
        ]);

        $response = $this->getJson("/api/delivery-challans/{$deliveryChallan->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_regular_user_cannot_view_another_users_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/invoices/{$invoice->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    public function test_regular_user_cannot_view_another_users_payment(): void
    {
        $payment = Payment::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/payments/{$payment->id}", $this->authHeaders());

        $response->assertNotFound();
    }

    // --- Ownership validation: restore ---

    public function test_regular_user_cannot_restore_another_users_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $quotation->delete();

        $response = $this->postJson("/api/quotations/{$quotation->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($quotation);
    }

    public function test_regular_user_cannot_restore_another_users_order(): void
    {
        $order = Order::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $order->delete();

        $response = $this->postJson("/api/orders/{$order->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($order);
    }

    public function test_regular_user_cannot_restore_another_users_sales_order(): void
    {
        $salesOrder = SalesOrder::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $salesOrder->delete();

        $response = $this->postJson("/api/sales-orders/{$salesOrder->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($salesOrder);
    }

    public function test_regular_user_cannot_restore_another_users_delivery_challan(): void
    {
        $deliveryChallan = DeliveryChallan::create([
            'serial' => 'BBDC/997/' . now()->format('y'),
            'delivery_date' => now()->toDateString(),
            'customer_id' => $this->otherCustomer->id,
            'delivery_address' => 'Address',
            'subtotal' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
            'created_by' => $this->otherUser->id,
        ]);
        $deliveryChallan->delete();

        $response = $this->postJson("/api/delivery-challans/{$deliveryChallan->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($deliveryChallan);
    }

    public function test_regular_user_cannot_restore_another_users_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $invoice->delete();

        $response = $this->postJson("/api/invoices/{$invoice->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($invoice);
    }

    public function test_regular_user_cannot_restore_another_users_payment(): void
    {
        $payment = Payment::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $payment->delete();

        $response = $this->postJson("/api/payments/{$payment->id}/restore", [], $this->authHeaders());

        $response->assertForbidden();
        $this->assertSoftDeleted($payment);
    }

    // --- Admin bypass ---

    public function test_admin_can_view_any_quotation(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);

        $response = $this->getJson("/api/quotations/{$quotation->id}", [
            'Authorization' => 'Bearer ' . auth('api')->login($admin),
        ]);

        $response->assertOk()->assertJsonPath('data.id', $quotation->id);
    }

    public function test_admin_can_restore_any_quotation(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->otherUser->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
        $quotation->delete();

        $response = $this->postJson("/api/quotations/{$quotation->id}/restore", [], [
            'Authorization' => 'Bearer ' . auth('api')->login($admin),
        ]);

        $response->assertOk();
        $this->assertNotSoftDeleted($quotation);
    }

    public function test_regular_user_can_view_own_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/quotations/{$quotation->id}", $this->authHeaders());

        $response->assertOk()->assertJsonPath('data.id', $quotation->id);
    }

    public function test_regular_user_can_restore_own_quotation(): void
    {
        $this->user->update(['role_id' => Role::where('slug', 'admin')->value('id')]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $quotation->delete();

        $response = $this->postJson("/api/quotations/{$quotation->id}/restore", [], $this->authHeaders());

        $response->assertOk();
        $this->assertNotSoftDeleted($quotation);
    }

    // --- Customer consistency: due-invoices ---

    public function test_regular_user_cannot_get_due_invoices_for_another_users_customer(): void
    {
        $response = $this->getJson(
            "/api/customers/{$this->otherCustomer->id}/due-invoices",
            $this->authHeaders(),
        );

        $response->assertForbidden();
    }

    public function test_admin_can_get_due_invoices_for_any_customer(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);

        $response = $this->getJson(
            "/api/customers/{$this->otherCustomer->id}/due-invoices",
            ['Authorization' => 'Bearer ' . auth('api')->login($admin)],
        );

        $response->assertOk();
    }

    // --- Customer consistency: remaining-items ---

    public function test_regular_user_cannot_get_remaining_items_for_another_users_sales_order(): void
    {
        $salesOrderItem = $this->createOtherUsersSalesOrderItem();

        $response = $this->getJson(
            "/api/sales-orders/{$salesOrderItem->sales_order_id}/remaining-items",
            $this->authHeaders(),
        );

        $response->assertForbidden();
    }

    public function test_regular_user_can_get_remaining_items_for_own_sales_order(): void
    {
        $orderItem = $this->createOrderItem($this->customer, $this->user);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'remaining_sales_quantity' => 5,
        ]);

        $response = $this->getJson(
            "/api/sales-orders/{$salesOrder->id}/remaining-items",
            $this->authHeaders(),
        );

        $response->assertOk()->assertJsonPath('data.id', $salesOrder->id);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.auth('api')->login($this->user)];
    }

    private function createOtherUsersOrderItem(): OrderItem
    {
        return $this->createOrderItem($this->otherCustomer, $this->otherUser);
    }

    private function createOrderItem(Customer $customer, User $user): OrderItem
    {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'remaining_order_quantity' => 10,
        ]);
    }

    private function createOtherUsersSalesOrderItem(): SalesOrderItem
    {
        return $this->createSalesOrderItem($this->otherCustomer, $this->otherUser);
    }

    private function createSalesOrderItem(Customer $customer, User $user): SalesOrderItem
    {
        $orderItem = $this->createOrderItem($customer, $user);
        $salesOrder = SalesOrder::factory()->create([
            'customer_id' => $customer->id,
            'created_by' => $user->id,
        ]);

        return SalesOrderItem::factory()->create([
            'sales_order_id' => $salesOrder->id,
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
            'remaining_sales_quantity' => 10,
        ]);
    }

    private function createOtherUsersDeliveryChallanItem(): DeliveryChallanItem
    {
        return $this->createDeliveryChallanItem($this->otherCustomer, $this->otherUser);
    }

    private function createDeliveryChallanItem(Customer $customer, User $user): DeliveryChallanItem
    {
        $salesOrderItem = $this->createSalesOrderItem($customer, $user);
        $deliveryChallan = DeliveryChallan::create([
            'serial' => 'BBDC/999/'.now()->format('y'),
            'delivery_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'delivery_address' => 'Other delivery address',
            'subtotal' => 10,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'grand_total' => 10,
            'created_by' => $user->id,
        ]);

        return DeliveryChallanItem::create([
            'delivery_challan_id' => $deliveryChallan->id,
            'sales_order_id' => $salesOrderItem->sales_order_id,
            'sales_order_item_id' => $salesOrderItem->id,
            'order_booking_id' => $salesOrderItem->order_id,
            'order_booking_item_id' => $salesOrderItem->order_item_id,
            'item_description' => 'Other users item',
            'unit' => 'pcs',
            'ordered_quantity' => 10,
            'delivered_quantity' => 10,
            'unit_price' => 1,
        ]);
    }
}
