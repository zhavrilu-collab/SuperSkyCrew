<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->string('retention_class')->default('ostalo');
            $table->boolean('tracks_expiry')->default(false);
            $table->boolean('is_system')->default(false);
            $table->string('print_key')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(50);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('person_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'expires_on']);
            $table->index(['organization_id', 'expires_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_documents');
        Schema::dropIfExists('document_types');
    }
};
