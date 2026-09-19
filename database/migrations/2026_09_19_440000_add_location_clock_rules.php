<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('require_photo')->default(false)->after('kiosk_token');
            $table->boolean('allow_offline')->default(true)->after('require_photo');
            $table->string('device_bind_mode', 16)->default('off')->after('allow_offline');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->string('clock_device_id')->nullable()->after('clock_pin');
        });

        Schema::table('punches', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('device_id');
            $table->timestamp('photo_taken_at')->nullable()->after('photo_path');
            $table->string('device_result', 16)->nullable()->after('geofence_result');
        });
    }

    public function down(): void
    {
        Schema::table('punches', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_taken_at', 'device_result']);
        });
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('clock_device_id');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['require_photo', 'allow_offline', 'device_bind_mode']);
        });
    }
};
