<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderStatusTransitionTest extends TestCase
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

    private function makeOrder(string $status = 'intake'): Order
    {
        return Order::factory()->create([
            'status' => $status,
            'created_by' => $this->user->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_valid_transitions_are_applied_and_persisted(): void
    {
        $validTransitions = [
            ['intake', 'to_procure'],
            ['intake', 'to_pack'],
            ['to_procure', 'to_pack'],
            ['to_pack', 'packed'],
            ['packed', 'dispatched'],
            ['dispatched', 'delivered'],
            ['dispatched', 'rto'],
        ];

        $service = app(OrderStatusTransitionService::class);

        foreach ($validTransitions as [$from, $to]) {
            $order = $this->makeOrder($from);
            $newStatus = OrderStatus::from($to);

            $updated = $service->transition($order, $newStatus);

            $this->assertSame($to, $updated->status->value);
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $to]);
        }
    }

    public function test_invalid_transitions_are_rejected_and_order_is_unchanged(): void
    {
        $invalidTransitions = [
            ['intake', 'packed'],
            ['intake', 'delivered'],
            ['to_procure', 'packed'],
            ['to_procure', 'dispatched'],
            ['to_pack', 'delivered'],
            ['packed', 'delivered'],
            ['delivered', 'packed'],
            ['rto', 'packed'],
        ];

        $service = app(OrderStatusTransitionService::class);

        foreach ($invalidTransitions as [$from, $to]) {
            $order = $this->makeOrder($from);

            try {
                $service->transition($order, OrderStatus::from($to));
                $this->fail("Transition {$from} -> {$to} should have been rejected.");
            } catch (InvalidArgumentException $e) {
                $this->assertSame($from, $order->fresh()->status->value);
                $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => $from]);
            }
        }
    }

    public function test_same_status_transition_is_rejected(): void
    {
        $order = $this->makeOrder('intake');
        $service = app(OrderStatusTransitionService::class);

        $this->assertFalse($service->canTransition('intake', 'intake'));

        $this->expectException(InvalidArgumentException::class);
        $service->transition($order, OrderStatus::Intake);
    }

    public function test_can_transition_uses_central_matrix(): void
    {
        $service = app(OrderStatusTransitionService::class);

        $this->assertTrue($service->canTransition('intake', 'to_pack'));
        $this->assertTrue($service->canTransition('dispatched', 'delivered'));
        $this->assertTrue($service->canTransition('dispatched', 'rto'));
        $this->assertFalse($service->canTransition('intake', 'packed'));
        $this->assertFalse($service->canTransition('delivered', 'to_procure'));
        $this->assertFalse($service->canTransition('rto', 'packed'));

        $this->assertArrayHasKey('intake', $service->allowedTransitions());
    }

    public function test_api_can_transition_order_status(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'to_pack',
        ], $this->authHeaders());

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Order status updated successfully',
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.status', 'to_pack');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'to_pack']);
    }

    public function test_api_rejects_invalid_enum_value(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'abc',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'intake']);
    }

    public function test_api_rejects_invalid_transition_with_business_error(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'delivered',
        ], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonPath('message', "Cannot transition order from 'intake' to 'delivered'.");

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'intake']);
    }

    public function test_api_requires_authentication(): void
    {
        $order = $this->makeOrder('intake');

        $response = $this->postJson("/api/orders/{$order->id}/status", [
            'status' => 'to_pack',
        ]);

        $response->assertUnauthorized();
    }
}
