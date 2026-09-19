<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('organization_id')->constrained('departments')->nullOnDelete();
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('organization_id')->constrained('departments')->nullOnDelete();
            $table->text('description')->nullable()->after('annual_leave_days');
        });
    }

    public function down(): void
    {
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('description');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
