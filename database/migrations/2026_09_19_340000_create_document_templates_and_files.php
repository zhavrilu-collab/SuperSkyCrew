<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('person_documents', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('note');
            $table->string('original_name')->nullable()->after('file_path');
            $table->string('mime')->nullable()->after('original_name');
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');

        Schema::table('person_documents', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'original_name', 'mime']);
        });
    }
};
