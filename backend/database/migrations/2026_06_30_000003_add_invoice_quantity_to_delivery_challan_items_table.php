<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->decimal('invoiced_quantity', 15, 2)->default(0)->after('delivered_quantity');
            $table->decimal('remaining_invoice_quantity', 15, 2)->default(0)->after('invoiced_quantity');
        });

        DB::statement('UPDATE delivery_challan_items SET remaining_invoice_quantity = delivered_quantity');
    }

    public function down(): void
    {
        Schema::table('delivery_challan_items', function (Blueprint $table) {
            $table->dropColumn(['invoiced_quantity', 'remaining_invoice_quantity']);
        });
    }
};
