<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_can_create_customer(): void
    {
        $payload = [
            'name' => 'Ram Kumar',
            'phone' => '9876543210',
            'email' => 'ram@example.com',
            'billing_address' => '123 Main St',
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'country' => 'India',
            'postal_code' => '700001',
        ];

        $response = $this->postJson('/api/customers', $payload, $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Customer created successfully',
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'ram@example.com',
            'phone' => '9876543210',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_customer_code_is_auto_generated(): void
    {
        $payload = [
            'name' => 'Sita Devi',
            'phone' => '9876543211',
            'billing_address' => '456 Park Ave',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'postal_code' => '400001',
        ];

        $response = $this->postJson('/api/customers', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('BBCU/', $response->json('data.customer_code'));
    }

    public function test_created_by_is_set_automatically(): void
    {
        $payload = [
            'name' => 'Test User',
            'phone' => '9876543212',
            'billing_address' => '789 Test Rd',
            'city' => 'Delhi',
            'state' => 'Delhi',
            'country' => 'India',
            'postal_code' => '110001',
        ];

        $response = $this->postJson('/api/customers', $payload, $this->authHeaders());

        $response->assertCreated();
        $this->assertEquals($this->user->id, $response->json('data.created_by.id'));
    }

    public function test_unauthenticated_user_cannot_create_customer(): void
    {
        $response = $this->postJson('/api/customers', [
            'name' => 'Unauthorized',
            'phone' => '9876543213',
        ]);

        $response->assertUnauthorized();
    }

    public function test_can_list_customers_with_pagination(): void
    {
        Customer::factory()->count(5)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_customers(): void
    {
        Customer::factory()->create([
            'name' => 'Searchable Name',
            'created_by' => $this->user->id,
        ]);
        Customer::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers?search=Searchable', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_status(): void
    {
        Customer::factory()->create([
            'status' => 'active',
            'created_by' => $this->user->id,
        ]);
        Customer::factory()->create([
            'status' => 'inactive',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/customers?status=active', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_customer(): void
    {
        $customer = Customer::factory()->create(['created_by' => $this->user->id]);

        $response = $this->getJson("/api/customers/{$customer->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $customer->id);
    }

    public function test_can_update_customer(): void
    {
        $this->user->update(['role_id' => 1]);

        $customer = Customer::factory()->create(['created_by' => $this->user->id]);

        $response = $this->putJson("/api/customers/{$customer->id}", [
            'name' => 'Updated Name',
            'phone' => $customer->phone,
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_can_soft_delete_customer(): void
    {
        $this->user->update(['role_id' => 1]);
        $customer = Customer::factory()->create(['created_by' => $this->user->id]);

        $response = $this->deleteJson("/api/customers/{$customer->id}", [], $this->authHeaders());

        $response->assertOk();
        $this->assertSoftDeleted($customer);
    }

    public function test_can_restore_customer(): void
    {
        $this->user->update(['role_id' => 1]);
        $customer = Customer::factory()->create(['created_by' => $this->user->id]);
        $customer->delete();

        $response = $this->postJson("/api/customers/{$customer->id}/restore", [], $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('message', 'Customer restored successfully');
        $this->assertNotSoftDeleted($customer);
    }

    public function test_validation_fails_on_duplicate_phone(): void
    {
        Customer::factory()->create([
            'phone' => '9999999999',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson('/api/customers', [
            'name' => 'Duplicate Phone',
            'phone' => '9999999999',
            'billing_address' => '123 Test',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'postal_code' => '123456',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }
}
