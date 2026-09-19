<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedInteger('employee_limit')->nullable()->after('plan');
            $table->json('features')->nullable()->after('employee_limit');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('clock_api_token', 64)->nullable()->after('clock_qr');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->json('allowed_channels')->nullable()->after('kiosk_token');
            $table->unsignedTinyInteger('punch_grace_minutes')->default(5)->after('allowed_channels');
            $table->unsignedTinyInteger('punch_round_minutes')->default(0)->after('punch_grace_minutes');
            $table->string('entrance_token', 64)->nullable()->after('punch_round_minutes');
            $table->string('terminal_token', 64)->nullable()->after('entrance_token');
        });

        Schema::create('grant_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name', 160);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('grant_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('grant_project_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->unsignedInteger('minutes');
            $table->string('note', 255)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'work_date']);
        });

        Schema::create('shift_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->unsignedBigInteger('request_id')->nullable();
            $table->timestamps();
            $table->unique(['person_id', 'work_date']);
        });

        Schema::create('open_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->unsignedTinyInteger('slots')->default(1);
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        foreach (DB::table('people')->whereNull('clock_api_token')->get(['id']) as $row) {
            DB::table('people')->where('id', $row->id)->update(['clock_api_token' => bin2hex(random_bytes(16))]);
        }

        Schema::table('people', function (Blueprint $table) {
            $table->unique('clock_api_token');
        });

        foreach (DB::table('locations')->get(['id', 'kiosk_token']) as $row) {
            DB::table('locations')->where('id', $row->id)->update([
                'entrance_token' => $row->kiosk_token ?: Str::random(32),
                'terminal_token' => Str::random(32),
            ]);
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->unique('entrance_token');
            $table->unique('terminal_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_shifts');
        Schema::dropIfExists('shift_overrides');
        Schema::dropIfExists('grant_entries');
        Schema::dropIfExists('grant_projects');

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['entrance_token']);
            $table->dropUnique(['terminal_token']);
            $table->dropColumn([
                'allowed_channels',
                'punch_grace_minutes',
                'punch_round_minutes',
                'entrance_token',
                'terminal_token',
            ]);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique(['clock_api_token']);
            $table->dropColumn('clock_api_token');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['employee_limit', 'features']);
        });
    }
};
