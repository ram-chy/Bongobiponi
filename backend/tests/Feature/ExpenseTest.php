<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ExpenseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role_id' => 1]);
        $this->category = ExpenseCategory::factory()->create(['created_by' => $this->user->id]);
    }

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function validPayload(): array
    {
        return [
            'expense_date' => now()->format('Y-m-d'),
            'category_id' => $this->category->id,
            'payment_method' => 'Cash',
            'amount' => 1500.00,
            'vendor_name' => 'Test Vendor',
            'remarks' => 'Test expense',
        ];
    }

    public function test_can_create_expense(): void
    {
        $response = $this->postJson('/api/expenses', $this->validPayload(), $this->authHeaders());

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Expense created successfully',
            ]);

        $this->assertDatabaseHas('expenses', [
            'vendor_name' => 'Test Vendor',
            'amount' => 1500.00,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_expense_no_is_auto_generated(): void
    {
        $response = $this->postJson('/api/expenses', $this->validPayload(), $this->authHeaders());

        $response->assertCreated();
        $this->assertStringStartsWith('BBEXP/', $response->json('data.expense_no'));
        $this->assertStringEndsWith('/' . now()->format('y'), $response->json('data.expense_no'));
    }

    public function test_can_list_expenses_with_pagination(): void
    {
        Expense::factory()->count(5)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/expenses', $this->authHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_can_search_expenses(): void
    {
        Expense::factory()->create([
            'expense_no' => 'BBEXP/001/26',
            'vendor_name' => 'Acme Corp',
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);
        Expense::factory()->count(3)->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/expenses?search=BBEXP/001', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_category(): void
    {
        $otherCategory = ExpenseCategory::factory()->create(['created_by' => $this->user->id]);

        Expense::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);
        Expense::factory()->create([
            'category_id' => $otherCategory->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/expenses?category_id={$this->category->id}", $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_filter_by_payment_method(): void
    {
        Expense::factory()->create([
            'payment_method' => 'Cash',
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);
        Expense::factory()->create([
            'payment_method' => 'Bank Transfer',
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson('/api/expenses?payment_method=Cash', $this->authHeaders());

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_can_view_expense(): void
    {
        $expense = Expense::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/expenses/{$expense->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $expense->id);
    }

    public function test_can_update_expense(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $expense = Expense::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->putJson("/api/expenses/{$expense->id}", [
            'vendor_name' => 'Updated Vendor',
            'amount' => 2500.00,
        ], $headers);

        $response->assertOk()
            ->assertJsonPath('data.vendor_name', 'Updated Vendor');
    }

    public function test_can_soft_delete_expense(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $expense = Expense::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->deleteJson("/api/expenses/{$expense->id}", [], $headers);

        $response->assertOk();
        $this->assertSoftDeleted($expense);
    }

    public function test_can_restore_expense(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $token = auth('api')->login($admin);
        $headers = ['Authorization' => "Bearer {$token}"];

        $expense = Expense::factory()->create([
            'category_id' => $this->category->id,
            'created_by' => $admin->id,
        ]);
        $expense->delete();

        $response = $this->postJson("/api/expenses/{$expense->id}/restore", [], $headers);

        $response->assertOk()
            ->assertJsonPath('message', 'Expense restored successfully');
        $this->assertNotSoftDeleted($expense);
    }

    public function test_can_upload_attachment(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('receipt.jpg', 100, 100);

        $payload = [...$this->validPayload(), 'attachment' => $file];

        $response = $this->postJson('/api/expenses', $payload, $this->authHeaders());

        $response->assertCreated();

        $expense = Expense::latest()->first();
        $this->assertNotNull($expense->attachment);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'attachment' => $expense->attachment,
        ]);
    }

    public function test_validation_fails_on_missing_required_fields(): void
    {
        $response = $this->postJson('/api/expenses', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'expense_date',
                'category_id',
                'payment_method',
                'amount',
                'vendor_name',
            ]);
    }

    public function test_validation_fails_on_invalid_payment_method(): void
    {
        $payload = $this->validPayload();
        $payload['payment_method'] = 'InvalidMethod';

        $response = $this->postJson('/api/expenses', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_validation_fails_on_zero_amount(): void
    {
        $payload = $this->validPayload();
        $payload['amount'] = 0;

        $response = $this->postJson('/api/expenses', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_unauthenticated_user_cannot_create_expense(): void
    {
        $response = $this->postJson('/api/expenses', $this->validPayload());

        $response->assertUnauthorized();
    }
}
