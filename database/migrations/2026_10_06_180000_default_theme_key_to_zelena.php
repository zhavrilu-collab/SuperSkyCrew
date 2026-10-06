<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')
            ->whereNotIn('theme_key', ['zelena', 'plava', 'crvena', 'zuta', 'narancasta'])
            ->update(['theme_key' => 'zelena']);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE organizations MODIFY theme_key VARCHAR(255) NOT NULL DEFAULT 'zelena'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE organizations ALTER COLUMN theme_key SET DEFAULT 'zelena'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE organizations MODIFY theme_key VARCHAR(255) NOT NULL DEFAULT 'tirkizna'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE organizations ALTER COLUMN theme_key SET DEFAULT 'tirkizna'");
        }
    }
};
