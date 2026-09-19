<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('mime');
            $table->string('kind', 40)->nullable()->after('is_system');
            $table->unique(['organization_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'kind']);
            $table->dropColumn(['is_system', 'kind']);
        });
    }
};
