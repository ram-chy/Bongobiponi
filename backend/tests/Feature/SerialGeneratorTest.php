<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Quotation;
use App\Services\DeliveryChallanSerialGeneratorService;
use App\Services\ExpenseSerialGeneratorService;
use App\Services\InvoiceSerialGeneratorService;
use App\Services\OrderSerialGeneratorService;
use App\Services\PaymentSerialGeneratorService;
use App\Services\QuotationSerialGeneratorService;
use App\Services\SalesOrderSerialGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_generator_follows_shared_format(): void
    {
        $generator = new QuotationSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBQ\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_order_generator_follows_shared_format(): void
    {
        $generator = new OrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BB\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_sales_order_generator_follows_shared_format(): void
    {
        $generator = new SalesOrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBSO\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_delivery_challan_generator_follows_shared_format(): void
    {
        $generator = new DeliveryChallanSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBDC\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_invoice_generator_follows_shared_format(): void
    {
        $generator = new InvoiceSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBINV\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_payment_generator_follows_shared_format(): void
    {
        $generator = new PaymentSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBPAY\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_expense_generator_follows_shared_format(): void
    {
        $generator = new ExpenseSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^BBEXP\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_generator_increments_after_existing_serial(): void
    {
        Quotation::factory()->create([
            'quotation_serial' => 'BBQ/007/' . now()->format('y'),
        ]);

        $generator = new QuotationSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('BBQ/008/' . now()->format('y'), $serial);
    }

    public function test_generator_ignores_other_prefix_serials(): void
    {
        Order::factory()->create([
            'order_serial' => 'BB/050/' . now()->format('y'),
        ]);

        $generator = new OrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('BB/051/' . now()->format('y'), $serial);
    }

    public function test_generator_includes_soft_deleted_records_in_count(): void
    {
        $quotation = Quotation::factory()->create([
            'quotation_serial' => 'BBQ/003/' . now()->format('y'),
        ]);
        $quotation->delete();

        $generator = new QuotationSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('BBQ/004/' . now()->format('y'), $serial);
    }
}
