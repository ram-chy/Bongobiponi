<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueExportTest extends TestCase
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

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/books/export')->assertUnauthorized();
    }

    public function test_export_returns_xlsx_download(): void
    {
        $publisher = Publisher::factory()->create(['created_by' => $this->user->id]);

        Book::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'publisher_id' => $publisher->id,
        ]);

        $response = $this->getJson('/api/books/export', $this->authHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_export_filename_contains_date(): void
    {
        Book::factory()->create(['created_by' => $this->user->id]);

        $response = $this->getJson('/api/books/export', $this->authHeaders());

        $disposition = $response->headers->get('content-disposition');

        $this->assertNotNull($disposition);
        $this->assertStringContainsString('book-catalogue-'.now()->format('Y-m-d').'.xlsx', $disposition);
    }

    public function test_export_works_with_empty_catalogue(): void
    {
        $response = $this->getJson('/api/books/export', $this->authHeaders());

        $response->assertOk();
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }
}
