<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CRITICAL DB FIX: sales_order_item_conversions.reference_id must match
        // order_item_conversions which was fixed to unsignedBigInteger in 2026_07_23 migration.
        // The signed integer overflows at 2,147,483,647 — use unsigned instead.

        Schema::table('sales_order_item_conversions', function (Blueprint $table) {
            $table->unsignedBigInteger('reference_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_item_conversions', function (Blueprint $table) {
            $table->bigInteger('reference_id')->change();
        });
    }
};