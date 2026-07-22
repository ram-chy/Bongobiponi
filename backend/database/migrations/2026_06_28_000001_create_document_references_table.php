<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_references', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->string('parent_document_type')->nullable();
            $table->unsignedBigInteger('parent_document_id')->nullable();
            $table->index(['uuid', 'document_type', 'document_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_references');
    }
};
