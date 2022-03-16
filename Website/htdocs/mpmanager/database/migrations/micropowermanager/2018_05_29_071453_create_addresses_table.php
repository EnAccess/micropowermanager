<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('micropowermanager')->create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('owner'); // adds owner_id and owner_type automatically
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('geo_id')->nullable();
            $table->integer('is_primary')->default(0);
            $table->timestamps();

            //email, phone, street, city ,province, country, geo reference
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('micropowermanager')->dropIfExists('adresses');
    }
};
