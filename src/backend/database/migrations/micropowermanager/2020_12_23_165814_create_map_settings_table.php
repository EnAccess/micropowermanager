<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('shard')->create('map_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('zoom');
            $table->double('latitude', 10);
            $table->double('longitude', 10);
            $table->string('provider')->nullable();
            $table->string('bingMapApiKey')->nullable();
            $table->timestamps();
        });

        DB::connection('shard')->table('map_settings')->insert([
            'zoom' => 7,
            'latitude' => -2.500380,
            'longitude' => 32.889060,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('shard')->dropIfExists('map_settings');
    }
};
