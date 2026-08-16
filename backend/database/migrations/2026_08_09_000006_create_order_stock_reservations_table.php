<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('status'); // active, released, consumed
            $table->unsignedBigInteger('consumed_by_inventory_transaction_id')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'book_id', 'status']);
            $table->index(['book_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_stock_reservations');
    }
};