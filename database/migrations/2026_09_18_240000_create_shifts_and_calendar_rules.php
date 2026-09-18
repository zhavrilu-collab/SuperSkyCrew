<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 16)->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedSmallInteger('break_minutes')->default(0);
            $table->boolean('is_night')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        Schema::create('calendar_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('level');
            $table->foreignId('department_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('job_position_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'level', 'weekday']);
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->foreignId('planned_shift_id')->nullable()->after('person_id')->constrained('shifts')->nullOnDelete();
            $table->timestamp('planned_start')->nullable()->after('planned_shift_id');
            $table->timestamp('planned_end')->nullable()->after('planned_start');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('planned_shift_id');
            $table->dropColumn(['planned_start', 'planned_end']);
        });
        Schema::dropIfExists('calendar_rules');
        Schema::dropIfExists('shifts');
    }
};
