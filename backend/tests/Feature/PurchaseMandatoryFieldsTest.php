<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Publisher;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseMandatoryFieldsTest extends TestCase
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

    private function supplier(): Supplier
    {
        return Supplier::factory()->create(['created_by' => $this->user->id]);
    }

    private function publisher(): Publisher
    {
        return Publisher::factory()->create(['created_by' => $this->user->id]);
    }

    private function book(): Book
    {
        return Book::factory()->create(['created_by' => $this->user->id]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'supplier_id' => $this->supplier()->id,
            'publisher_id' => $this->publisher()->id,
            'purchase_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'book_id' => $this->book()->id,
                    'received_quantity' => 10,
                    'purchase_price' => 100,
                    'printed_price' => 250,
                    'discount_percentage' => 20,
                ],
            ],
        ], $overrides);
    }

    public function test_publisher_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['publisher_id']);

        $this->postJson('/api/purchases', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('publisher_id');
    }

    public function test_printed_price_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['items'][0]['printed_price']);

        $this->postJson('/api/purchases', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.printed_price');
    }

    public function test_discount_percentage_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['items'][0]['discount_percentage']);

        $this->postJson('/api/purchases', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.discount_percentage');
    }

    public function test_discount_percentage_cannot_exceed_100(): void
    {
        $payload = $this->validPayload();
        $payload['items'][0]['discount_percentage'] = 101;

        $this->postJson('/api/purchases', $payload, $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.discount_percentage');
    }

    public function test_store_persists_printed_price_and_discount(): void
    {
        $payload = $this->validPayload();

        $response = $this->postJson('/api/purchases', $payload, $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.publisher_id', $payload['publisher_id'])
            ->assertJsonPath('data.items.0.printed_price', '250.00')
            ->assertJsonPath('data.items.0.discount_percentage', '20.00');

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $response->json('data.id'),
            'printed_price' => 250.00,
            'discount_percentage' => 20.00,
        ]);
    }

    public function test_update_draft_requires_item_fields_when_items_sent(): void
    {
        $created = $this->postJson('/api/purchases', $this->validPayload(), $this->authHeaders())->assertCreated();
        $purchaseId = $created->json('data.id');

        $this->putJson("/api/purchases/{$purchaseId}", [
            'items' => [
                [
                    'book_id' => $this->book()->id,
                    'received_quantity' => 5,
                    'purchase_price' => 90,
                ],
            ],
        ], $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.printed_price', 'items.0.discount_percentage']);
    }

    public function test_update_draft_requires_publisher_when_provided(): void
    {
        $created = $this->postJson('/api/purchases', $this->validPayload(), $this->authHeaders())->assertCreated();
        $purchaseId = $created->json('data.id');

        $this->putJson("/api/purchases/{$purchaseId}", [
            'publisher_id' => null,
        ], $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('publisher_id');
    }
}
