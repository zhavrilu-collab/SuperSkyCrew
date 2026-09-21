<?php

use App\Services\NkzOccupationImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nkz_occupations', function (Blueprint $table) {
            $table->string('code', 4)->primary();
            $table->string('title');
            $table->unsignedTinyInteger('level')->default(4);
            $table->timestamps();
        });

        app(NkzOccupationImporter::class)->seedFromSnapshot();
    }

    public function down(): void
    {
        Schema::dropIfExists('nkz_occupations');
    }
};
