<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // inventory_transactions.book_id: cascadeOnDelete → restrictOnDelete
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->restrictOnDelete();
        });

        // stocks.book_id: cascadeOnDelete → restrictOnDelete
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->restrictOnDelete();
        });

        // receive_orders.supplier_id: cascadeOnDelete → restrictOnDelete
        Schema::table('receive_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });

        // receive_order_items.book_id: cascadeOnDelete → restrictOnDelete
        Schema::table('receive_order_items', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->restrictOnDelete();
        });

        // purchases.supplier_id: cascadeOnDelete → restrictOnDelete
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
        });

        // purchase_items.book_id: cascadeOnDelete → restrictOnDelete
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });

        Schema::table('receive_orders', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });

        Schema::table('receive_order_items', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->foreign('book_id')->references('id')->on('books')->cascadeOnDelete();
        });
    }
};
