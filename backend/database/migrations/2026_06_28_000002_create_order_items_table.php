<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type');
            $table->integer('item_no')->nullable();
            $table->string('description');
            $table->string('unit', 50);
            $table->decimal('quoted_quantity', 15, 2)->nullable();
            $table->decimal('ordered_quantity', 15, 2);
            $table->decimal('remaining_order_quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->text('price_snapshot')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->text('discount_snapshot')->nullable();
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->text('tax_snapshot')->nullable();
            $table->decimal('line_total', 15, 2);
            $table->text('remarks')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
