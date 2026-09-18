<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->timestamp('exception_resolved_at')->nullable()->after('exception_code');
            $table->foreignId('exception_resolved_by')->nullable()->after('exception_resolved_at')->constrained('users')->nullOnDelete();
            $table->string('exception_note', 255)->nullable()->after('exception_resolved_by');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exception_resolved_by');
            $table->dropColumn(['exception_resolved_at', 'exception_note']);
        });
    }
};
