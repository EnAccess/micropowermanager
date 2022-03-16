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
    public function up(): void
    {
        Schema::connection('micropowermanager')->create('sub_connection_types', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('connection_type_id');
            $table->integer('tariff_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::connection('micropowermanager')->dropIfExists('sub_connection_types');
    }
};
