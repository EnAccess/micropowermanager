<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->rename('assets', 'appliances');
        Schema::connection('tenant')->rename('asset_types', 'appliance_types');
        Schema::connection('tenant')->rename('asset_people', 'appliance_people');
        Schema::connection('tenant')->rename('asset_rates', 'appliance_rates');

        Schema::connection('tenant')->table('appliances', function (Blueprint $table) {
            if (Schema::hasColumn('appliances', 'asset_type_id')) {
                $table->renameColumn('asset_type_id', 'appliance_type_id');
            }
        });


        Schema::connection('tenant')->table('appliance_people', function (Blueprint $table) {
            if (Schema::hasColumn('appliance_people', 'asset_type_id')) {
                $table->renameColumn('asset_type_id', 'appliance_type_id');
            }
            if (Schema::hasColumn('appliance_people', 'asset_id')) {
                $table->renameColumn('asset_id', 'appliance_id');
            }
        });

        Schema::connection('tenant')->table('appliance_rates', function (Blueprint $table) {
            if (Schema::hasColumn('appliance_rates', 'asset_person_id')) {
                $table->renameColumn('asset_person_id', 'appliance_person_id');
            }
        });

        Schema::connection('tenant')->table('solar_home_systems', function (Blueprint $table) {
            if (Schema::hasColumn('solar_home_systems', 'asset_id')) {
                $table->renameColumn('asset_id', 'appliance_id');
            }
        });

    }

    public function down(): void
    {
        Schema::connection('tenant')->table('appliances', function (Blueprint $table) {
            if (Schema::hasColumn('appliances', 'appliance_type_id')) {
                $table->renameColumn('appliance_type_id', 'asset_type_id');
            }
        });

        Schema::connection('tenant')->rename('appliance_types', 'asset_types');
        Schema::connection('tenant')->rename('appliances', 'assets');
        Schema::connection('tenant')->rename('appliance_people', 'asset_people');
        Schema::connection('tenant')->rename('appliance_rates', 'asset_rates');

    }
};
