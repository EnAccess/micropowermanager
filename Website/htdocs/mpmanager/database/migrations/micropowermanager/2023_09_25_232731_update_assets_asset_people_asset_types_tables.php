<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return  new class extends Migration
{
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
            $table->renameColumn('asset_type_id', 'asset_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
