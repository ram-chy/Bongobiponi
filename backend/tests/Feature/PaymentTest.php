<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);
        $this->invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'grand_total' => 5000.00,
            'paid_amount' => 0,
            'payment_status' => 'Unpaid',
            'created_by' => $this->user->id,
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
            'payment_date' => now()->format('Y-m-d'),
            'payment_method' => 'Cash',
            'reference_no' => 'REF-001',
            'remarks' => 'Test payment',
            'items' => [
                [
                    'invoice_id' => $this->invoice->id,
                    'paid_amount' => 2000.00,
                    'remarks' => 'Partial payment',
                ],
            ],
        ];
    }

    public function test_can_create_payment(): void
    {
        $response = $this->postJson('/api/payments', $this->validPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Payment created successfully',
            ]);

        $this->assertDatabaseHas('payments', [
            'customer_id' => $this->customer->id,
            'total_amount' => 2000.00,
            'payment_status' => 'Partially Paid',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('payment_items', [
            'invoice_id' => $this->invoice->id,
            'paid_amount' => 2000.00,
        ]);
    }

    public function test_payment_no_is_auto_generated(): void
    {
        $response = $this->postJson('/api/payments', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('GGPAY/', $response->json('data.payment_no'));
        $this->assertStringEndsWith('/' . now()->format('y'), $response->json('data.payment_no'));
    }

    public function test_invoice_payment_status_updates_after_payment(): void
    {
        $this->postJson('/api/payments', $this->validPayload(), $this->authHeaders());

        $this->invoice->refresh();

        $this->assertEquals(2000.00, (float) $this->invoice->paid_amount);
        $this->assertEquals('Partially Paid', $this->invoice->payment_status);
    }

    public function test_invoice_payment_status_becomes_paid_when_fully_paid(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['paid_amount'] = 5000.00;

        $response = $this->postJson('/api/payments', $payload, $this->authHeaders());

        $this->invoice->refresh();

        $this->assertEquals(5000.00, (float) $this->invoice->paid_amount);
        $this->assertEquals('Paid', $this->invoice->payment_status);
        $this->assertEquals('Paid', $response->json('data.payment_status'));
    }

    public function test_invoice_validation_paid_amount_exceeds_due(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['paid_amount'] = 6000.00;

        $response = $this->postJson('/api/payments', $payload, $this->authHeaders());

        $response->assertStatus(422);
    }

    public function test_can_list_payments_with_pagination(): void
    {
        Payment::factory()->count(5)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/payments', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_payments(): void
    {
        Payment::factory()->create([
            'payment_no' => 'GGPAY/001/26',
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        Payment::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/payments?search=GGPAY/001', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_payment_method(): void
    {
        Payment::factory()->create([
            'payment_method' => 'Cash',
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);
        Payment::factory()->create([
            'payment_method' => 'Bank Transfer',
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/payments?payment_method=Cash', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_payment(): void
    {
        $payment = Payment::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/payments/{$payment->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $payment->id)
            ->assertJsonPath('data.payment_status', 'Paid');
    }

    public function test_can_update_payment(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $payment = Payment::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/payments/{$payment->id}", [
            'reference_no' => 'REF-UPDATED',
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.reference_no', 'REF-UPDATED');
    }

    public function test_can_update_payment_with_increased_amount(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        Invoice::where('id', $this->invoice->id)->update(['created_by' => $admin->id]);
        $this->invoice->refresh();

        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $payment = $this->postJson('/api/payments', $this->validPayload(), $headers);
        $paymentId = $payment->json('data.id');

        $response = $this->putJson("/api/payments/{$paymentId}", [
            'items' => [
                [
                    'invoice_id' => $this->invoice->id,
                    'paid_amount' => 5000.00,
                ],
            ],
        ], $headers);

        $response->assertOk();

        $this->invoice->refresh();
        $this->assertEquals('Paid', $this->invoice->payment_status);
        $this->assertEquals(5000.00, (float) $this->invoice->paid_amount);
        $this->assertEquals('Paid', $response->json('data.payment_status'));
    }

    public function test_can_soft_delete_payment(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $payment = Payment::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson("/api/payments/{$payment->id}", [], $headers);

        $response->assertOk();
        $this->assertSoftDeleted($payment);
    }

    public function test_can_restore_payment(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $payment = Payment::factory()->create([
            'customer_id' => $this->customer->id,
            'created_by' => $admin->id,
        ]);
        $payment->delete();

        $response = $this->postJson("/api/payments/{$payment->id}/restore", [], $headers);

        $response->assertOk()
            ->assertJsonPath('message', 'Payment restored successfully');
        $this->assertNotSoftDeleted($payment);
    }

    public function test_unauthenticated_user_cannot_create_payment(): void
    {
        $response = $this->postJson('/api/payments', $this->validPayload());

        $response->assertUnauthorized();
    }

    public function test_can_get_due_invoices(): void
    {
        $response = $this->getJson("/api/customers/{$this->customer->id}/due-invoices", $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->invoice->id, $response->json('data.0.id'));
        $this->assertEquals(5000.00, (float) $response->json('data.0.due_amount'));
    }

    public function test_validation_fails_on_invalid_payment_method(): void
    {
        $payload = $this->validPayload();
        $payload['payment_method'] = 'InvalidMethod';

        $response = $this->postJson('/api/payments', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_invoice_payment_status_resets_when_payment_deleted(): void
    {
        $response = $this->postJson('/api/payments', $this->validPayload(), $this->authHeaders());
        $paymentId = $response->json('data.id');

        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $response = $this->deleteJson("/api/payments/{$paymentId}", [], $headers);

        $response->assertOk();

        $this->invoice->refresh();
        $this->assertEquals(0, (float) $this->invoice->paid_amount);
        $this->assertEquals('Unpaid', $this->invoice->payment_status);
    }
}
