<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->boolean('is_shift')->default(false)->after('is_night');
        });

        Schema::table('time_entries', function (Blueprint $table) {
            $table->unsignedInteger('shift_minutes')->default(0)->after('split_shift_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('shift_minutes');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('is_shift');
        });
    }
};
