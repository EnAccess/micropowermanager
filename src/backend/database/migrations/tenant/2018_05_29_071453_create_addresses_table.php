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
        Schema::connection('tenant')->create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('owner'); // adds owner_id and owner_type automatically
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('geo_id')->nullable();
            $table->integer('is_primary')->default(0);
            $table->timestamps();

            // email, phone, street, city ,province, country, geo reference
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('addresses');
    }
};
