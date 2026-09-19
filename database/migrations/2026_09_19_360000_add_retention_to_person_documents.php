<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('person_documents', 'retain_until')) {
            Schema::table('person_documents', function (Blueprint $table) {
                $table->date('retain_until')->nullable()->after('expires_on');
                $table->timestamp('retention_proposed_at')->nullable()->after('retain_until');
                $table->timestamp('disposed_at')->nullable()->after('retention_proposed_at');
                $table->foreignId('disposed_by')->nullable()->after('disposed_at')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('person_documents', 'person_docs_retention_idx')) {
            Schema::table('person_documents', function (Blueprint $table) {
                $table->index(['organization_id', 'retention_proposed_at', 'disposed_at'], 'person_docs_retention_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::table('person_documents', function (Blueprint $table) {
            $table->dropIndex('person_docs_retention_idx');
            $table->dropConstrainedForeignId('disposed_by');
            $table->dropColumn(['retain_until', 'retention_proposed_at', 'disposed_at']);
        });
    }
};
