<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedTinyInteger('annual_leave_days_per_child')->default(0)->after('annual_leave_base_days');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->boolean('annual_leave_manual')->default(false)->after('annual_leave_days');
        });

        Schema::create('leave_tenure_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('min_years');
            $table->unsignedTinyInteger('extra_days');
            $table->timestamps();

            $table->unique(['organization_id', 'min_years']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_tenure_rules');
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('annual_leave_manual');
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('annual_leave_days_per_child');
        });
    }
};
