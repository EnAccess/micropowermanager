<?php

use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('shard')->table('asset_types', function (Blueprint $table) {
            $table->dropColumn('price');
        });
        Schema::connection('shard')->table('assets', function (Blueprint $table) {
            $table->renameColumn('default_price', 'price');
            $table->dropColumn('default_rate');
        });
        Schema::connection('shard')->table('asset_people', function (Blueprint $table) {
            $table->renameColumn('asset_type_id', 'asset_id');
        });
        Schema::connection('shard')->table('agent_assigned_appliances', function (Blueprint $table) {
            $table->renameColumn('appliance_type_id', 'appliance_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->table('agent_assigned_appliances', function (Blueprint $table) {
            $table->renameColumn('appliance_id', 'appliance_type_id');
        });
        Schema::connection('shard')->table('asset_people', function (Blueprint $table) {
            $table->renameColumn('asset_id', 'asset_type_id');
        });
        Schema::connection('shard')->table('assets', function (Blueprint $table) {
            $table->renameColumn('price', 'default_price');
            $table->integer('default_rate');
        });
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }
        Schema::connection('shard')->table('asset_types', function (Blueprint $table) {
            $table->double('price', 15, 6)->nullable();
        });
    }
};
