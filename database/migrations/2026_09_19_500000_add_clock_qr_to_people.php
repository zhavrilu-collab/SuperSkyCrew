<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('clock_qr', 32)->nullable()->after('clock_pin');
        });

        $seen = [];
        foreach (DB::table('people')->orderBy('id')->get(['id', 'organization_id']) as $row) {
            do {
                $token = bin2hex(random_bytes(8));
            } while (isset($seen[$row->organization_id][$token]));
            $seen[$row->organization_id][$token] = true;
            DB::table('people')->where('id', $row->id)->update(['clock_qr' => $token]);
        }

        Schema::table('people', function (Blueprint $table) {
            $table->unique(['organization_id', 'clock_qr']);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'clock_qr']);
            $table->dropColumn('clock_qr');
        });
    }
};
