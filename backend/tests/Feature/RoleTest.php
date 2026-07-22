<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_assigns_regular_user_role(): void
    {
        $payload = [
            'first_name' => 'Ram',
            'last_name' => 'Chow',
            'email' => 'ram@example.com',
            'mobile_no' => '1234567891',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.user.role.slug', 'regular_user');
    }

    public function test_login_response_includes_role(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.role.slug', 'admin');
    }

    public function test_me_endpoint_includes_role(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJsonPath('data.role.slug', 'regular_user');
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        User::factory()->count(5)->create();
        $token = auth('api')->login($admin);

        $response = $this->getJson('/api/users', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJsonCount(6, 'data');
    }

    public function test_admin_can_view_user(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $target = User::factory()->create();
        $token = auth('api')->login($admin);

        $response = $this->getJson("/api/users/{$target->id}", ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJsonPath('data.id', $target->id);
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $user = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->getJson('/api/users', ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $target = User::factory()->create([
            'role_id' => Role::where('slug', 'regular_user')->value('id'),
        ]);
        $token = auth('api')->login($admin);

        $managerRoleId = Role::where('slug', 'manager')->value('id');

        $response = $this->putJson("/api/users/{$target->id}/role", [
            'role_id' => $managerRoleId,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJsonPath('data.role.slug', 'manager');
    }

    public function test_non_admin_cannot_update_role(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $token = auth('api')->login($user);

        $response = $this->putJson("/api/users/{$target->id}/role", [
            'role_id' => 1,
        ], ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_role_middleware_allows_admin(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $token = auth('api')->login($admin);

        $response = $this->getJson('/api/users', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk();
    }

    public function test_role_middleware_blocks_manager(): void
    {
        $manager = User::factory()->create([
            'role_id' => Role::where('slug', 'manager')->value('id'),
        ]);
        $token = auth('api')->login($manager);

        $response = $this->getJson('/api/users', ['Authorization' => "Bearer {$token}"]);

        $response->assertForbidden();
    }

    public function test_regular_user_sees_only_own_customers(): void
    {
        $regular = User::factory()->create([
            'role_id' => Role::where('slug', 'regular_user')->value('id'),
        ]);
        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'regular_user')->value('id'),
        ]);

        $token = auth('api')->login($regular);

        \App\Models\Customer::factory()->count(3)->create(['created_by' => $regular->id]);
        \App\Models\Customer::factory()->count(2)->create(['created_by' => $other->id]);

        $response = $this->getJson('/api/customers', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_sees_all_customers(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $other = User::factory()->create();

        $token = auth('api')->login($admin);

        \App\Models\Customer::factory()->count(3)->create(['created_by' => $admin->id]);
        \App\Models\Customer::factory()->count(2)->create(['created_by' => $other->id]);

        $response = $this->getJson('/api/customers', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }
}
