<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_meters')->nullable();
            $table->string('geofence_mode')->default('off');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('oib', 11)->nullable();
            $table->string('gender', 16)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('citizenship')->nullable();
            $table->string('residence')->nullable();
            $table->string('job_title')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('status')->default('employee');
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->string('ended_reason')->nullable();
            $table->date('insurance_filed_at')->nullable();
            $table->date('work_permit_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'oib']);
        });

        Schema::create('punches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('correction_of_id')->nullable()->constrained('punches')->nullOnDelete();
            $table->string('type');
            $table->string('channel');
            $table->timestamp('occurred_at_device');
            $table->timestamp('occurred_at_server');
            $table->string('device_id')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('gps_accuracy')->nullable();
            $table->string('geofence_result')->default('skipped');
            $table->boolean('offline')->default(false);
            $table->uuid('client_event_id')->nullable();
            $table->string('reason')->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('integrity_hash', 64);
            $table->timestamps();

            $table->unique(['organization_id', 'client_event_id']);
            $table->index(['person_id', 'occurred_at_server']);
        });

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('downtime_minutes')->default(0);
            $table->unsignedInteger('total_minutes')->default(0);
            $table->unsignedInteger('field_work_minutes')->default(0);
            $table->unsignedInteger('standby_minutes')->default(0);
            $table->unsignedInteger('night_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('sunday_minutes')->default(0);
            $table->unsignedInteger('holiday_minutes')->default(0);
            $table->unsignedInteger('evidential_minutes')->default(0);
            $table->string('absence_code')->nullable();
            $table->unsignedInteger('absence_minutes')->default(0);
            $table->string('status')->default('draft');
            $table->string('exception_code')->nullable();
            $table->timestamps();

            $table->unique(['person_id', 'work_date']);
            $table->index(['organization_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('punches');
        Schema::dropIfExists('people');
        Schema::dropIfExists('locations');
    }
};
