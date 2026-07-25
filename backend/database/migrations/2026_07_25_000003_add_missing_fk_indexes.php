<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PERFORMANCE FIX: Add indexes on frequently queried FK columns
        // These were identified as missing in the code audit.

        // Sales order items FK indexes
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->index('sales_order_id');
            $table->index('order_id');
            $table->index('order_item_id');
            $table->index('quotation_id');
            $table->index('quotation_item_id');
        });

        // Order items FK indexes
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id');
            $table->index('quotation_id');
            $table->index('quotation_item_id');
        });

        // Quotation items FK index
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->index('quotation_id');
        });

        // Delivery challan items FK indexes
        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->index('delivery_challan_id');
            $table->index('sales_order_id');
            $table->index('sales_order_item_id');
            $table->index('order_booking_id');
            $table->index('order_booking_item_id');
            $table->index('quotation_id');
            $table->index('quotation_item_id');
        });

        // Invoice items FK indexes
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index('invoice_id');
            $table->index('delivery_challan_id');
            $table->index('delivery_challan_item_id');
            $table->index('sales_order_id');
            $table->index('sales_order_item_id');
            $table->index('order_booking_id');
            $table->index('order_booking_item_id');
            $table->index('quotation_id');
            $table->index('quotation_item_id');
        });

        // Payment items FK indexes
        Schema::table('payment_items', function (Blueprint $table) {
            $table->index('payment_id');
            $table->index('invoice_id');
        });

        // Receive order items FK indexes
        Schema::table('receive_order_items', function (Blueprint $table) {
            $table->index('receive_order_id');
            $table->index('book_id');
        });

        // Purchase items FK indexes
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->index('purchase_id');
            $table->index('book_id');
        });

        // Master data created_by/updated_by indexes
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('updated_by');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('updated_by');
        });

        // Inventory transactions FK indexes
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->index('book_id');
            $table->index('created_by');
            $table->index('reference_type');
        });

        // Sales order item conversions FK index
        Schema::table('sales_order_item_conversions', function (Blueprint $table) {
            $table->index('sales_order_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropIndex(['sales_order_id']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['order_item_id']);
            $table->dropIndex(['quotation_id']);
            $table->dropIndex(['quotation_item_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropIndex(['quotation_id']);
            $table->dropIndex(['quotation_item_id']);
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropIndex(['quotation_id']);
        });

        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->dropIndex(['delivery_challan_id']);
            $table->dropIndex(['sales_order_id']);
            $table->dropIndex(['sales_order_item_id']);
            $table->dropIndex(['order_booking_id']);
            $table->dropIndex(['order_booking_item_id']);
            $table->dropIndex(['quotation_id']);
            $table->dropIndex(['quotation_item_id']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['delivery_challan_id']);
            $table->dropIndex(['delivery_challan_item_id']);
            $table->dropIndex(['sales_order_id']);
            $table->dropIndex(['sales_order_item_id']);
            $table->dropIndex(['order_booking_id']);
            $table->dropIndex(['order_booking_item_id']);
            $table->dropIndex(['quotation_id']);
            $table->dropIndex(['quotation_item_id']);
        });

        Schema::table('payment_items', function (Blueprint $table) {
            $table->dropIndex(['payment_id']);
            $table->dropIndex(['invoice_id']);
        });

        Schema::table('receive_order_items', function (Blueprint $table) {
            $table->dropIndex(['receive_order_id']);
            $table->dropIndex(['book_id']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropIndex(['purchase_id']);
            $table->dropIndex(['book_id']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['updated_by']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex(['book_id']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['reference_type']);
        });

        Schema::table('sales_order_item_conversions', function (Blueprint $table) {
            $table->dropIndex(['sales_order_item_id']);
        });
    }
};