<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('social_tariff_piggy_banks', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('social_tariff_piggy_banks', 'meter_parameter_id')) {
                $table->dropColumn('meter_parameter_id');
            }
            if (!Schema::connection('tenant')->hasColumn('social_tariff_piggy_banks', 'meter_id')) {
                $table->integer('meter_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('social_tariff_piggy_banks', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('social_tariff_piggy_banks', 'meter_parameter_id')) {
                $table->integer('meter_parameter_id');
            }
            if (Schema::connection('tenant')->hasColumn('social_tariff_piggy_banks', 'meter_id')) {
                $table->dropColumn('meter_id');
            }
        });
    }
};
