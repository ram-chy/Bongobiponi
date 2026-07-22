<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationTest extends TestCase
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

    private function validPayload(): array
    {
        return [
            'customer_id' => $this->customer->id,
            'quotation_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays(15)->format('Y-m-d'),
            'items' => [
                [
                    'description' => 'Product A',
                    'quantity' => 10,
                    'unit' => 'pcs',
                    'unit_price' => 100,
                    'discount_percentage' => 10,
                    'tax_percentage' => 18,
                ],
                [
                    'description' => 'Service B',
                    'quantity' => 2,
                    'unit' => 'hours',
                    'unit_price' => 500,
                    'discount_percentage' => 0,
                    'tax_percentage' => 18,
                ],
            ],
        ];
    }

    public function test_can_create_quotation(): void
    {
        $response = $this->postJson('/api/quotations', $this->validPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Quotation created successfully',
            ]);

        $this->assertDatabaseHas('quotations', [
            'customer_id' => $this->customer->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseCount('quotation_items', 2);
    }

    public function test_quotation_serial_is_auto_generated(): void
    {
        $response = $this->postJson('/api/quotations', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('GGQ/', $response->json('data.quotation_serial'));
        $this->assertStringEndsWith('/' . now()->format('y'), $response->json('data.quotation_serial'));
    }

    public function test_created_by_is_set_automatically(): void
    {
        $response = $this->postJson('/api/quotations', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals($this->user->id, $response->json('data.created_by.id'));
    }

    public function test_totals_are_calculated_automatically(): void
    {
        $response = $this->postJson('/api/quotations', $this->validPayload(), $this->authHeaders());

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

    public function test_unauthenticated_user_cannot_create_quotation(): void
    {
        $response = $this->postJson('/api/quotations', $this->validPayload());

        $response->assertUnauthorized();
    }

    public function test_can_list_quotations_with_pagination(): void
    {
        Quotation::factory()->count(5)->create(['created_by' => $this->user->id, 'customer_id' => $this->customer->id]);

        $response = $this->getJson('/api/quotations', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_quotations(): void
    {
        Quotation::factory()->create([
            'quotation_serial' => 'GGQ/999/26',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Quotation::factory()->count(3)->create(['created_by' => $this->user->id, 'customer_id' => $this->customer->id]);

        $response = $this->getJson('/api/quotations?search=999', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status(): void
    {
        Quotation::factory()->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        Quotation::factory()->create([
            'status' => 'sent',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/quotations?status=draft', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_quotation(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/quotations/{$quotation->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $quotation->id);
    }

    public function test_can_update_quotation(): void
    {
        $this->user->update(['role_id' => 1]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->putJson("/api/quotations/{$quotation->id}", [
            'notes' => 'Updated notes',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_can_soft_delete_quotation(): void
    {
        $this->user->update(['role_id' => 1]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->deleteJson("/api/quotations/{$quotation->id}", [], $this->authHeaders());

        $response->assertOk();
        $this->assertSoftDeleted($quotation);
    }

    public function test_can_restore_quotation(): void
    {
        $this->user->update(['role_id' => 1]);
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
        $quotation->delete();

        $response = $this->postJson("/api/quotations/{$quotation->id}/restore", [], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('message', 'Quotation restored successfully');
        $this->assertNotSoftDeleted($quotation);
    }

    public function test_quotation_must_have_at_least_one_item(): void
    {
        $payload = $this->validPayload();
        $payload['items'] = [];

        $response = $this->postJson('/api/quotations', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_download_pdf(): void
    {
        $quotation = Quotation::factory()->create([
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->getJson("/api/quotations/{$quotation->id}/download-pdf", $this->authHeaders());

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
    }

    public function test_serial_resets_yearly(): void
    {
        // Create quotation in current year
        Quotation::factory()->create([
            'quotation_serial' => 'GGQ/005/' . now()->format('y'),
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->postJson('/api/quotations', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals('GGQ/006/' . now()->format('y'), $response->json('data.quotation_serial'));
    }
}
