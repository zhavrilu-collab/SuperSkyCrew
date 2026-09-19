<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('oib', 11)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 80)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
        });

        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'name']);
        });

        Schema::create('enterprise_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('enterprise_units')->nullOnDelete();
            $table->foreignId('legal_entity_id')->nullable()->constrained('legal_entities')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained('work_centers')->nullOnDelete();
            $table->string('name');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'name']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('enterprise_unit_id')->nullable()->after('parent_id')->constrained('enterprise_units')->nullOnDelete();
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreignId('legal_entity_id')->nullable()->after('organization_id')->constrained('legal_entities')->nullOnDelete();
        });

        Schema::table('people', function (Blueprint $table) {
            $table->foreignId('legal_entity_id')->nullable()->after('cost_center_id')->constrained('legal_entities')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->after('legal_entity_id')->constrained('work_centers')->nullOnDelete();
        });

        Schema::table('person_engagements', function (Blueprint $table) {
            $table->foreignId('legal_entity_id')->nullable()->after('cost_center_id')->constrained('legal_entities')->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->after('legal_entity_id')->constrained('work_centers')->nullOnDelete();
        });

        $now = now();
        foreach (DB::table('organizations')->orderBy('id')->get() as $organization) {
            $legalId = DB::table('legal_entities')->insertGetId([
                'organization_id' => $organization->id,
                'parent_id' => null,
                'name' => $organization->name,
                'code' => 'SJ',
                'oib' => $organization->oib,
                'street' => null,
                'city' => $organization->city,
                'country' => 'Hrvatska',
                'valid_from' => $now->toDateString(),
                'valid_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $unitId = DB::table('enterprise_units')->insertGetId([
                'organization_id' => $organization->id,
                'parent_id' => null,
                'legal_entity_id' => $legalId,
                'work_center_id' => null,
                'name' => $organization->name,
                'valid_from' => $now->toDateString(),
                'valid_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('departments')->where('organization_id', $organization->id)->update([
                'enterprise_unit_id' => $unitId,
            ]);
            DB::table('cost_centers')->where('organization_id', $organization->id)->update([
                'legal_entity_id' => $legalId,
            ]);
            DB::table('people')->where('organization_id', $organization->id)->update([
                'legal_entity_id' => $legalId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('person_engagements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_center_id');
            $table->dropConstrainedForeignId('legal_entity_id');
        });
        Schema::table('people', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_center_id');
            $table->dropConstrainedForeignId('legal_entity_id');
        });
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legal_entity_id');
        });
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enterprise_unit_id');
        });
        Schema::dropIfExists('enterprise_units');
        Schema::dropIfExists('work_centers');
        Schema::dropIfExists('legal_entities');
    }
};
