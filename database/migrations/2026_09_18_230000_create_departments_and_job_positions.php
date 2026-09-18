<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
        });

        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rad1g', 16)->nullable();
            $table->unsignedTinyInteger('annual_leave_days')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('location_id')->constrained()->nullOnDelete();
            $table->foreignId('job_position_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_position_id');
            $table->dropConstrainedForeignId('department_id');
        });
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('departments');
    }
};
