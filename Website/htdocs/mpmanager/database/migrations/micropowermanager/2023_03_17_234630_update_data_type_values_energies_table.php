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
        Schema::connection('shard')->table('energies', function (Blueprint $table) {
            $table->double('used_energy_since_last',15,2)->default(0)->change();
            $table->double('total_absorbed',15,2)->default(0)->change();
            $table->double('absorbed_energy_since_last',15,2)->default(0)->change();
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->table('energies', function (Blueprint $table) {
            //
        });
    }
};
