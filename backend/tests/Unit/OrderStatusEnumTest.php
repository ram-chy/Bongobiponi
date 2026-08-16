<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusEnumTest extends TestCase
{
    public function test_enum_contains_exactly_the_workflow_states(): void
    {
        $this->assertSame(
            ['intake', 'to_procure', 'to_pack', 'packed', 'dispatched', 'delivered', 'rto', 'cancelled'],
            array_map(fn (OrderStatus $status) => $status->value, OrderStatus::cases()),
        );
    }

    public function test_values_match_database_convention(): void
    {
        $this->assertSame('intake', OrderStatus::Intake->value);
        $this->assertSame('to_procure', OrderStatus::ToProcure->value);
        $this->assertSame('to_pack', OrderStatus::ToPack->value);
        $this->assertSame('packed', OrderStatus::Packed->value);
        $this->assertSame('dispatched', OrderStatus::Dispatched->value);
        $this->assertSame('delivered', OrderStatus::Delivered->value);
        $this->assertSame('rto', OrderStatus::Rto->value);
        $this->assertSame('cancelled', OrderStatus::Cancelled->value);
    }

    public function test_labels_are_readable(): void
    {
        $this->assertSame('Intake', OrderStatus::Intake->label());
        $this->assertSame('To Procure', OrderStatus::ToProcure->label());
        $this->assertSame('To Pack', OrderStatus::ToPack->label());
        $this->assertSame('Packed', OrderStatus::Packed->label());
        $this->assertSame('Dispatched', OrderStatus::Dispatched->label());
        $this->assertSame('Delivered', OrderStatus::Delivered->label());
        $this->assertSame('RTO', OrderStatus::Rto->label());
        $this->assertSame('Cancelled', OrderStatus::Cancelled->label());
    }

    public function test_delivered_rto_and_cancelled_are_final_states(): void
    {
        $this->assertTrue(OrderStatus::Delivered->isFinal());
        $this->assertTrue(OrderStatus::Rto->isFinal());
        $this->assertTrue(OrderStatus::Cancelled->isFinal());

        foreach ([OrderStatus::Intake, OrderStatus::ToProcure, OrderStatus::ToPack, OrderStatus::Packed, OrderStatus::Dispatched] as $status) {
            $this->assertFalse($status->isFinal());
        }
    }
}
