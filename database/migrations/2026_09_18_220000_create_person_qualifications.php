<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('title');
            $table->string('issuer')->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('required_for_job')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'expires_at']);
            $table->index(['organization_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_qualifications');
    }
};
