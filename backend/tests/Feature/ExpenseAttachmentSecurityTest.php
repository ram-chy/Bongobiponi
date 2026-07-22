<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private array $authHeaders;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->value('id'),
        ]);
        $token = auth('api')->login($this->user);
        $this->authHeaders = ['Authorization' => "Bearer {$token}"];
    }

    public function test_attachment_stored_on_private_disk(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->postJson('/api/expenses', $this->validPayload($file), $this->authHeaders);

        $response->assertCreated();

        $path = $response->json('data.attachment');
        $this->assertNotNull($path);

        $expense = Expense::latest()->first();
        $this->assertNotNull($expense->attachment);
        $this->assertStringStartsWith('expenses/', $expense->attachment);
        $this->assertFalse(Storage::disk('public')->exists($expense->attachment));
    }

    public function test_attachment_url_returns_download_endpoint(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->postJson('/api/expenses', $this->validPayload($file), $this->authHeaders);

        $response->assertCreated();
        $attachmentUrl = $response->json('data.attachment');
        $this->assertStringContainsString('/download-attachment', $attachmentUrl);
        $this->assertStringNotContainsString('/storage/', $attachmentUrl);
    }

    public function test_download_endpoint_serves_file_from_private_disk(): void
    {
        $expense = Expense::factory()->create([
            'attachment' => 'expenses/test-file.pdf',
            'created_by' => $this->user->id,
        ]);

        Storage::disk('local')->put('expenses/test-file.pdf', 'pdf-content-here');

        $response = $this->getJson(
            "/api/expenses/{$expense->id}/download-attachment",
            $this->authHeaders
        );

        $response->assertOk();
        $response->assertHeader('Content-Disposition');
    }

    public function test_download_returns_404_for_missing_file(): void
    {
        $expense = Expense::factory()->create([
            'attachment' => 'expenses/nonexistent.jpg',
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            "/api/expenses/{$expense->id}/download-attachment",
            $this->authHeaders
        );

        $response->assertNotFound();
    }

    public function test_download_returns_404_when_no_attachment(): void
    {
        $expense = Expense::factory()->create([
            'attachment' => null,
            'created_by' => $this->user->id,
        ]);

        $response = $this->getJson(
            "/api/expenses/{$expense->id}/download-attachment",
            $this->authHeaders
        );

        $response->assertNotFound();
    }

    private function validPayload(?UploadedFile $file = null): array
    {
        $payload = [
            'expense_date' => now()->toDateString(),
            'category_id' => \App\Models\ExpenseCategory::factory()->create()->id,
            'payment_method' => 'Cash',
            'amount' => 100.00,
            'vendor_name' => 'Test Vendor',
        ];

        if ($file) {
            $payload['attachment'] = $file;
        }

        return $payload;
    }
}
