<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Performance indexes for search/filter queries on non-unique columns
        // Note: Only add if the column doesn't already have any index

        // Customers: email for search queries (email is unique, but we need a non-unique index for LIKE '%...%')
        Schema::table('customers', function (Blueprint $table) {
            $table->index('email');
        });

        // Suppliers: email and phone for search queries
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
        });
    }
};