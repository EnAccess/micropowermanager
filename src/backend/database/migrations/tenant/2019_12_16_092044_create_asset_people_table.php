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
        Schema::connection('tenant')->create('asset_people', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('asset_type_id');
            $table->integer('person_id');
            $table->integer('total_cost');
            $table->integer('rate_count');
            $table->morphs('creator');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('asset_people');
    }
};
