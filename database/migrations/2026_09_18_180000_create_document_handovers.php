<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('handed_on');
            $table->string('recipient');
            $table->string('purpose');
            $table->string('document_kind');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'handed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_handovers');
    }
};
