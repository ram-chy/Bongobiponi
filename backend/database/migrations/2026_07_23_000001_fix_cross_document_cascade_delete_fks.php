<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CRIT-005/016: Fix cross-document cascade chains
        // Changing cascadeOnDelete to restrictOnDelete on FKs that cross document boundaries
        // This prevents deleting an Order from destroying all downstream SalesOrders, DeliveryChallans, Invoices

        // sales_order_items: cross-document FKs
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();

            $table->dropForeign(['order_item_id']);
            $table->foreign('order_item_id')->references('id')->on('order_items')->restrictOnDelete();
        });

        // delivery_challan_items: cross-document FKs
        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();

            $table->dropForeign(['sales_order_item_id']);
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->restrictOnDelete();

            $table->dropForeign(['order_booking_id']);
            $table->foreign('order_booking_id')->references('id')->on('orders')->restrictOnDelete();

            $table->dropForeign(['order_booking_item_id']);
            $table->foreign('order_booking_item_id')->references('id')->on('order_items')->restrictOnDelete();
        });

        // invoice_items: cross-document FKs
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_challan_id']);
            $table->foreign('delivery_challan_id')->references('id')->on('delivery_challans')->restrictOnDelete();

            $table->dropForeign(['delivery_challan_item_id']);
            $table->foreign('delivery_challan_item_id')->references('id')->on('delivery_challan_items')->restrictOnDelete();

            $table->dropForeign(['sales_order_id']);
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->restrictOnDelete();

            $table->dropForeign(['sales_order_item_id']);
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->restrictOnDelete();

            $table->dropForeign(['order_booking_id']);
            $table->foreign('order_booking_id')->references('id')->on('orders')->restrictOnDelete();

            $table->dropForeign(['order_booking_item_id']);
            $table->foreign('order_booking_item_id')->references('id')->on('order_items')->restrictOnDelete();
        });

        // payment_items: invoice_id already fixed to nullOnDelete in 2026_07_17 migration
        // No changes needed here

        // Also fix expense_categories.user FKs missed by previous fix migration
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->dropForeign(['updated_by']);
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });

        // CRIT-014: Add unique constraint to book_author pivot to prevent duplicate author assignments
        Schema::table('book_author', function (Blueprint $table) {
            $table->unique(['book_id', 'author_id']);
        });

        // CRIT-015: Fix activity_logs.user_id FK to nullOnDelete (currently cascades)
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // CRIT-017: Fix order_item_conversions.reference_id to unsignedBigInteger (must match parent PK type)
        Schema::table('order_item_conversions', function (Blueprint $table) {
            $table->unsignedBigInteger('reference_id')->change();
        });
    }

    public function down(): void
    {
        // Revert cross-document FKs back to cascadeOnDelete

        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->dropForeign(['order_item_id']);
            $table->foreign('order_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });

        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();

            $table->dropForeign(['sales_order_item_id']);
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->cascadeOnDelete();

            $table->dropForeign(['order_booking_id']);
            $table->foreign('order_booking_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->dropForeign(['order_booking_item_id']);
            $table->foreign('order_booking_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_challan_id']);
            $table->foreign('delivery_challan_id')->references('id')->on('delivery_challans')->cascadeOnDelete();

            $table->dropForeign(['delivery_challan_item_id']);
            $table->foreign('delivery_challan_item_id')->references('id')->on('delivery_challan_items')->cascadeOnDelete();

            $table->dropForeign(['sales_order_id']);
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->cascadeOnDelete();

            $table->dropForeign(['sales_order_item_id']);
            $table->foreign('sales_order_item_id')->references('id')->on('sales_order_items')->cascadeOnDelete();

            $table->dropForeign(['order_booking_id']);
            $table->foreign('order_booking_id')->references('id')->on('orders')->cascadeOnDelete();

            $table->dropForeign(['order_booking_item_id']);
            $table->foreign('order_booking_item_id')->references('id')->on('order_items')->cascadeOnDelete();
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreign('created_by')->references('id')->on('users');

            $table->dropForeign(['updated_by']);
            $table->foreign('updated_by')->references('id')->on('users');
        });

        Schema::table('book_author', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'author_id']);
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('order_item_conversions', function (Blueprint $table) {
            $table->integer('reference_id')->change();
        });
    }
};
