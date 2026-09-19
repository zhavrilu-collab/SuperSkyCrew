<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('cv_path')->nullable()->after('clock_pin');
            $table->string('cv_original_name')->nullable()->after('cv_path');
            $table->timestamp('cv_uploaded_at')->nullable()->after('cv_original_name');
        });

        Schema::create('person_interview_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->date('occurred_on');
            $table->foreignId('interviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('interviewer_name')->nullable();
            $table->string('outcome');
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['person_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_interview_notes');
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['cv_path', 'cv_original_name', 'cv_uploaded_at']);
        });
    }
};
