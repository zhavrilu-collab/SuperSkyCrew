<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->string('client_ip', 45)->nullable()->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->dropColumn('client_ip');
        });
    }
};
