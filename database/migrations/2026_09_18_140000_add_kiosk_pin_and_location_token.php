<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('kiosk_enabled')->default(false);
            $table->string('kiosk_token', 64)->nullable();
            $table->unique('kiosk_token');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('clock_pin', 8)->nullable();
            $table->unique(['organization_id', 'clock_pin']);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'clock_pin']);
            $table->dropColumn('clock_pin');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['kiosk_token']);
            $table->dropColumn(['kiosk_enabled', 'kiosk_token']);
        });
    }
};
