<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role_id' => 1]);
    }

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function validPayload(): array
    {
        return [
            'name' => 'Office Supplies',
            'description' => 'General office supplies',
            'is_active' => true,
        ];
    }

    public function test_can_create_expense_category(): void
    {
        $response = $this->postJson('/api/expense-categories', $this->validPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Expense category created successfully',
            ]);

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Office Supplies',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_can_list_expense_categories(): void
    {
        ExpenseCategory::factory()->count(5)->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/expense-categories', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_expense_categories(): void
    {
        ExpenseCategory::factory()->create([
            'name' => 'Office Rent',
            'created_by' => $this->user->id,
        ]);
        ExpenseCategory::factory()->count(3)->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/expense-categories?search=Office', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_expense_category(): void
    {
        $category = ExpenseCategory::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/expense-categories/{$category->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $category->id);
    }

    public function test_can_update_expense_category(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $category = ExpenseCategory::factory()->create([
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/expense-categories/{$category->id}", [
            'name' => 'Updated Category',
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Category');
    }

    public function test_can_soft_delete_expense_category(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $category = ExpenseCategory::factory()->create([
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson("/api/expense-categories/{$category->id}", [], $headers);

        $response->assertOk();
        $this->assertSoftDeleted($category);
    }

    public function test_can_restore_expense_category(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $category = ExpenseCategory::factory()->create([
            'created_by' => $admin->id,
        ]);
        $category->delete();

        $response = $this->postJson("/api/expense-categories/{$category->id}/restore", [], $headers);

        $response->assertOk()
            ->assertJsonPath('message', 'Expense category restored successfully');
        $this->assertNotSoftDeleted($category);
    }

    public function test_name_is_required(): void
    {
        $response = $this->postJson('/api/expense-categories', [
            'description' => 'Test',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_name_must_be_unique(): void
    {
        ExpenseCategory::factory()->create([
            'name' => 'Office Rent',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson('/api/expense-categories', [
            'name' => 'Office Rent',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/expense-categories');

        $response->assertUnauthorized();
    }
}
