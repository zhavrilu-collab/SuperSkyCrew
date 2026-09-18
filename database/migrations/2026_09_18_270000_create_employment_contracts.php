<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('contract_type')->nullable();
            $table->string('number')->nullable();
            $table->date('signed_at');
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->date('trial_ends_at')->nullable();
            $table->unsignedTinyInteger('weekly_hours')->nullable();
            $table->boolean('is_current')->default(true);
            $table->string('note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['person_id', 'is_current']);
            $table->index(['organization_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
