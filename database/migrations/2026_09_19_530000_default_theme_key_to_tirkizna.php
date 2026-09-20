<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')->update(['theme_key' => 'tirkizna']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE organizations MODIFY theme_key VARCHAR(255) NOT NULL DEFAULT 'tirkizna'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE organizations ALTER COLUMN theme_key SET DEFAULT 'tirkizna'");
        } elseif ($driver === 'sqlite') {
            // SQLite ignores column defaults on ALTER; factory and OrganizationThemes::DEFAULT cover new rows.
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE organizations MODIFY theme_key VARCHAR(255) NOT NULL DEFAULT 'zelena'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE organizations ALTER COLUMN theme_key SET DEFAULT 'zelena'");
        }
    }
};
