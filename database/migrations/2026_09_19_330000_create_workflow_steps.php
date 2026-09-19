<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('role', 32);
            $table->unsignedTinyInteger('min_days')->nullable();
            $table->unsignedTinyInteger('max_days')->nullable();
            $table->timestamps();

            $table->unique(['workflow_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
