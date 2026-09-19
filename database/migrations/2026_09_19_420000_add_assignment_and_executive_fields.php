<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('host_employer')->nullable()->after('instrument_title');
            $table->boolean('assignment_clocks')->default(true)->after('host_employer');
            $table->boolean('executive_autonomy')->default(false)->after('assignment_clocks');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['host_employer', 'assignment_clocks', 'executive_autonomy']);
        });
    }
};
