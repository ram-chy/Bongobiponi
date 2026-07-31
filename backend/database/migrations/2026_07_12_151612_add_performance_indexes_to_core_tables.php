<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->index('status');
            $table->index('city');
            $table->index('state');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('order_source');
            $table->index('order_date');
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('sales_order_date');
        });

        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->index('status');
            $table->index('delivery_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('status');
            $table->index('invoice_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_method');
            $table->index('payment_date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('payment_method');
            $table->index('expense_date');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['city']);
            $table->dropIndex(['state']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['order_source']);
            $table->dropIndex(['order_date']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['sales_order_date']);
        });

        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['delivery_date']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['invoice_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['payment_date']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['expense_date']);
        });
    }
};
