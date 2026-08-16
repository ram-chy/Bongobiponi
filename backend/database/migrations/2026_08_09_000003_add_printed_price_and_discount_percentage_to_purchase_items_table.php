<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('printed_price', 12, 2)->nullable()->default(0)->after('purchase_price');
            $table->decimal('discount_percentage', 5, 2)->nullable()->default(0)->after('printed_price');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['printed_price', 'discount_percentage']);
        });
    }
};
