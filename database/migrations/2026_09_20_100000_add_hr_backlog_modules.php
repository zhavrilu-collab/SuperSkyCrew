<?php

use App\Models\JobPosition;
use App\Models\OrgPosition;
use App\Models\Person;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('mbs', 32)->nullable()->after('oib');
            $table->string('street')->nullable()->after('phone');
            $table->string('website')->nullable()->after('city');
            $table->string('nkd', 16)->nullable()->after('website');
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->after('country');
            $table->string('court')->nullable()->after('iban');
            $table->string('capital')->nullable()->after('court');
            $table->string('signatories')->nullable()->after('capital');
        });

        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->decimal('gross_salary', 12, 2)->nullable()->after('weekly_hours');
            $table->unsignedSmallInteger('notice_days')->nullable()->after('gross_salary');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('email')->nullable()->after('residence');
            $table->string('phone', 50)->nullable()->after('email');
            $table->foreignId('dotted_manager_user_id')->nullable()->after('manager_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('deputy_user_id')->nullable()->after('manager_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('job_positions', function (Blueprint $table) {
            $table->string('pay_grade', 32)->nullable()->after('annual_leave_days');
            $table->text('duties')->nullable()->after('description');
            $table->text('requirements')->nullable()->after('duties');
        });

        Schema::create('business_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('description')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
        });

        Schema::table('enterprise_units', function (Blueprint $table) {
            $table->foreignId('business_segment_id')->nullable()->after('work_center_id')->constrained()->nullOnDelete();
        });

        Schema::create('org_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('seat_no')->default(1);
            $table->string('status', 20)->default('open');
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'job_position_id', 'seat_no']);
            $table->index(['organization_id', 'status']);
        });

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('org_position_id')->nullable()->after('job_position_id')->constrained('org_positions')->nullOnDelete();
        });

        Schema::create('person_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('kin', 20);
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_dependent')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->string('phone', 50)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'person_id']);
        });

        Schema::create('competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 20)->default('hard');
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('job_position_competency', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('required_level')->default(1);
            $table->timestamps();

            $table->unique(['job_position_id', 'competency_id']);
        });

        Schema::create('internal_acts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind', 20)->default('pravilnik');
            $table->string('version', 32)->nullable();
            $table->boolean('must_read')->default(false);
            $table->date('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'kind']);
        });

        $seats = [];
        Person::query()
            ->whereNotNull('job_position_id')
            ->orderBy('id')
            ->each(function (Person $person) use (&$seats) {
                $jobId = (int) $person->job_position_id;
                $seats[$jobId] = ($seats[$jobId] ?? 0) + 1;
                $job = JobPosition::query()->find($jobId);
                $seat = OrgPosition::query()->create([
                    'organization_id' => $person->organization_id,
                    'job_position_id' => $jobId,
                    'department_id' => $person->department_id ?: $job?->department_id,
                    'seat_no' => $seats[$jobId],
                    'status' => 'filled',
                    'person_id' => $person->id,
                    'valid_from' => $person->started_at?->toDateString() ?: $job?->valid_from?->toDateString(),
                    'valid_to' => null,
                ]);
                $person->forceFill(['org_position_id' => $seat->id])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('org_position_id');
        });
        Schema::dropIfExists('internal_acts');
        Schema::dropIfExists('job_position_competency');
        Schema::dropIfExists('competencies');
        Schema::dropIfExists('person_family_members');
        Schema::dropIfExists('org_positions');
        Schema::table('enterprise_units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_segment_id');
        });
        Schema::dropIfExists('business_segments');
        Schema::table('job_positions', function (Blueprint $table) {
            $table->dropColumn(['pay_grade', 'duties', 'requirements']);
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deputy_user_id');
        });
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone']);
            $table->dropConstrainedForeignId('dotted_manager_user_id');
        });
        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->dropColumn(['gross_salary', 'notice_days']);
        });
        Schema::table('legal_entities', function (Blueprint $table) {
            $table->dropColumn(['iban', 'court', 'capital', 'signatories']);
        });
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['mbs', 'street', 'website', 'nkd']);
        });
    }
};
