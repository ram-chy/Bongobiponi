<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $authHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $token = auth('api')->login($this->user);
        $this->authHeaders = ['Authorization' => "Bearer {$token}"];
    }

    public function test_per_page_is_clamped_to_100(): void
    {
        Customer::factory()->count(150)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers?per_page=999', $this->authHeaders);

        $response->assertOk();
        $this->assertCount(100, $response->json('data'));
        $this->assertEquals(150, $response->json('meta.total'));
    }

    public function test_per_page_accepts_100(): void
    {
        Customer::factory()->count(110)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers?per_page=100', $this->authHeaders);

        $response->assertOk();
        $this->assertCount(100, $response->json('data'));
    }

    public function test_per_page_defaults_to_15(): void
    {
        Customer::factory()->count(20)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers', $this->authHeaders);

        $response->assertOk();
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }

    public function test_per_page_with_invalid_value_defaults_to_15(): void
    {
        Customer::factory()->count(20)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers?per_page=abc', $this->authHeaders);

        $response->assertOk();
        $this->assertCount(15, $response->json('data'));
    }

    public function test_per_page_with_zero_defaults_to_15(): void
    {
        Customer::factory()->count(20)->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/customers?per_page=0', $this->authHeaders);

        $response->assertOk();
        $this->assertCount(15, $response->json('data'));
    }
}
