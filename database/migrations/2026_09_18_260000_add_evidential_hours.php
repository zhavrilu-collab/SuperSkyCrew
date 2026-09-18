<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absence_codes', function (Blueprint $table) {
            $table->string('kind', 16)->default('absence')->after('category');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->boolean('evidential_manual')->default(false)->after('evidential_minutes');
            $table->string('evidential_code', 16)->nullable()->after('evidential_manual');
            $table->string('evidential_note')->nullable()->after('evidential_code');
            $table->foreignId('evidential_cost_center_id')->nullable()->after('evidential_note')->constrained('cost_centers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evidential_cost_center_id');
            $table->dropColumn(['evidential_manual', 'evidential_code', 'evidential_note']);
        });
        Schema::table('absence_codes', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
