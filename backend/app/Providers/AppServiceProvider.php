<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\DeliveryChallan;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\DeliveryChallanPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InventoryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\QuotationPolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Customer::class => CustomerPolicy::class,
        Order::class => OrderPolicy::class,
        Quotation::class => QuotationPolicy::class,
        SalesOrder::class => SalesOrderPolicy::class,
        DeliveryChallan::class => DeliveryChallanPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        Expense::class => ExpensePolicy::class,
        ExpenseCategory::class => ExpenseCategoryPolicy::class,
        User::class => UserPolicy::class,
        Purchase::class => PurchasePolicy::class,
        InventoryTransaction::class => InventoryPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();

        Relation::morphMap([
            'App\Models\Purchase' => Purchase::class,
            'App\Models\SalesOrder' => SalesOrder::class,
            'App\Models\Quotation' => Quotation::class,
            'App\Models\Order' => Order::class,
        ]);

        $this->loadMigrationsFrom([
            database_path('migrations'),
            database_path('migrations/auth'),
            database_path('migrations/customer'),
            database_path('migrations/quotation'),
            database_path('migrations/order'),
            database_path('migrations/system'),
            database_path('migrations/sales_order'),
            database_path('migrations/expense'),
        ]);
    }
}
