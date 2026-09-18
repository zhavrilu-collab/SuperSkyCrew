<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('manager_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('annual_leave_days')->default(20)->after('work_permit_expires_at');
        });

        Schema::create('absence_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16);
            $table->string('name');
            $table->string('category')->default('other');
            $table->boolean('paid')->default(true);
            $table->boolean('consumes_annual_leave')->default(false);
            $table->boolean('is_system')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('entitled_days')->default(20);
            $table->unsignedTinyInteger('carried_days')->default(0);
            $table->unsignedTinyInteger('used_days')->default(0);
            $table->timestamps();

            $table->unique(['person_id', 'year']);
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['organization_id', 'type']);
        });

        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->string('current_role')->nullable();
            $table->unsignedTinyInteger('step_index')->default(0);
            $table->json('approval_path')->nullable();
            $table->json('payload');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['person_id', 'status']);
        });

        Schema::create('workflow_request_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_request_actions');
        Schema::dropIfExists('workflow_requests');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('absence_codes');

        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_user_id');
            $table->dropColumn('annual_leave_days');
        });
    }
};
