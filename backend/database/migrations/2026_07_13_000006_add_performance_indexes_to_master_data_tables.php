<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('status');
            $table->index('name');
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->index('status');
            $table->index('name');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->index('status');
            $table->index('name');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('status');
            $table->index('parent_id');
            $table->index('name');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->index('status');
            $table->index('publisher_id');
            $table->index('category_id');
            $table->index('title');
        });

        Schema::table('book_author', function (Blueprint $table) {
            $table->index(['book_id', 'author_id']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['name']);
        });

        Schema::table('publishers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['name']);
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['name']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['name']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['publisher_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['title']);
        });

        Schema::table('book_author', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'author_id']);
        });
    }
};
