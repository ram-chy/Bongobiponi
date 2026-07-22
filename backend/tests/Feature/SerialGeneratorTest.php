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

        $this->assertMatchesRegularExpression('/^GGQ\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_order_generator_follows_shared_format(): void
    {
        $generator = new OrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GG\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_sales_order_generator_follows_shared_format(): void
    {
        $generator = new SalesOrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GGSO\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_delivery_challan_generator_follows_shared_format(): void
    {
        $generator = new DeliveryChallanSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GGDC\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_invoice_generator_follows_shared_format(): void
    {
        $generator = new InvoiceSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GGINV\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_payment_generator_follows_shared_format(): void
    {
        $generator = new PaymentSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GGPAY\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_expense_generator_follows_shared_format(): void
    {
        $generator = new ExpenseSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertMatchesRegularExpression('/^GGEXP\/\d{3}\/\d{2}$/', $serial);
    }

    public function test_generator_increments_after_existing_serial(): void
    {
        Quotation::factory()->create([
            'quotation_serial' => 'GGQ/007/' . now()->format('y'),
        ]);

        $generator = new QuotationSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('GGQ/008/' . now()->format('y'), $serial);
    }

    public function test_generator_ignores_other_prefix_serials(): void
    {
        Order::factory()->create([
            'order_serial' => 'GG/050/' . now()->format('y'),
        ]);

        $generator = new OrderSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('GG/051/' . now()->format('y'), $serial);
    }

    public function test_generator_includes_soft_deleted_records_in_count(): void
    {
        $quotation = Quotation::factory()->create([
            'quotation_serial' => 'GGQ/003/' . now()->format('y'),
        ]);
        $quotation->delete();

        $generator = new QuotationSerialGeneratorService();
        $serial = $generator->generate();

        $this->assertEquals('GGQ/004/' . now()->format('y'), $serial);
    }
}
