<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('organization_type')->default('company')->after('plan');
            $table->string('theme_key')->default('zelena')->after('organization_type');
            $table->string('logo_path')->nullable()->after('theme_key');
            $table->boolean('volunteer_module')->default(false)->after('logo_path');
            $table->unsignedTinyInteger('expiry_warning_days')->default(30)->after('volunteer_module');
            $table->unsignedTinyInteger('annual_leave_base_days')->default(20)->after('expiry_warning_days');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'organization_type',
                'theme_key',
                'logo_path',
                'volunteer_module',
                'expiry_warning_days',
                'annual_leave_base_days',
            ]);
        });
    }
};
