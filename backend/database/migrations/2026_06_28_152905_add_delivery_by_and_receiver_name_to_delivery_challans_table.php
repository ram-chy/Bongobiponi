<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->string('delivery_by', 255)->nullable()->after('status');
            $table->string('receiver_name', 255)->nullable()->after('delivery_by');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->dropColumn(['delivery_by', 'receiver_name']);
        });
    }
};
