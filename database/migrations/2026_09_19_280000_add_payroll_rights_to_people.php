<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('iban', 34)->nullable();
            $table->decimal('pay_coefficient', 8, 4)->nullable();
            $table->decimal('allowance_percent', 6, 2)->nullable();
            $table->unsignedSmallInteger('prior_service_months')->nullable();
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->unsignedTinyInteger('dependents_count')->default(0);
            $table->string('tax_relief_note')->nullable();
            $table->string('family_right')->nullable();
            $table->boolean('znr_exam_required')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn([
                'iban',
                'pay_coefficient',
                'allowance_percent',
                'prior_service_months',
                'children_count',
                'dependents_count',
                'tax_relief_note',
                'family_right',
                'znr_exam_required',
            ]);
        });
    }
};
