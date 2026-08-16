<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCommentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role_id' => 1]);
        $this->customer = Customer::factory()->create(['created_by' => $this->user->id]);
    }

    private function authHeaders(): array
    {
        $token = auth('api')->login($this->user);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function makeOrder(): Order
    {
        return Order::factory()->create([
            'status' => 'intake',
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_api_requires_authentication_for_comments(): void
    {
        $order = $this->makeOrder();

        $this->getJson("/api/orders/{$order->id}/comments")->assertUnauthorized();
        $this->postJson("/api/orders/{$order->id}/comments", ['comment' => 'hi'])->assertUnauthorized();
    }

    public function test_regular_user_cannot_view_other_users_order_comments(): void
    {
        $order = $this->makeOrder();

        $otherUser = User::factory()->create(['role_id' => 3]);
        $token = auth('api')->login($otherUser);

        $response = $this->getJson("/api/orders/{$order->id}/comments", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertNotFound();
    }

    public function test_store_comment_requires_comment_text(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->id}/comments", [], $this->authHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('comment');
    }

    public function test_store_comment_persists_with_authenticated_user(): void
    {
        $order = $this->makeOrder();

        $response = $this->postJson("/api/orders/{$order->id}/comments", [
            'comment' => 'Client called to follow up on delivery.',
        ], $this->authHeaders());

        $response->assertCreated()
            ->assertJsonPath('data.comment', 'Client called to follow up on delivery.')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.user.id', $this->user->id);

        $this->assertDatabaseHas('order_comments', [
            'order_id' => $order->id,
            'user_id' => $this->user->id,
            'comment' => 'Client called to follow up on delivery.',
        ]);
    }

    public function test_list_comments_returns_all_comments_newest_first(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->id}/comments", ['comment' => 'First comment'], $this->authHeaders())->assertCreated();
        $this->postJson("/api/orders/{$order->id}/comments", ['comment' => 'Second comment'], $this->authHeaders())->assertCreated();

        $response = $this->getJson("/api/orders/{$order->id}/comments", $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.comment', 'Second comment')
            ->assertJsonPath('data.1.comment', 'First comment');
    }

    public function test_list_comments_returns_empty_array_for_order_without_comments(): void
    {
        $order = $this->makeOrder();

        $this->getJson("/api/orders/{$order->id}/comments", $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_soft_deleting_order_keeps_comments_on_disk(): void
    {
        $order = $this->makeOrder();

        $this->postJson("/api/orders/{$order->id}/comments", ['comment' => 'Kept after soft delete'], $this->authHeaders())->assertCreated();

        $this->deleteJson("/api/orders/{$order->id}", [], $this->authHeaders())->assertOk();

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_comments', [
            'order_id' => $order->id,
            'comment' => 'Kept after soft delete',
        ]);
    }
}
