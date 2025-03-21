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
        Schema::connection('tenant')->create('sub_targets', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('target_id');
            $table->integer('connection_id');
            $table->unsignedInteger('revenue')->default(0);
            $table->unsignedInteger('new_connections')->default(0);
            $table->double('connected_power');
            $table->double('energy_per_month');
            $table->double('average_revenue_per_month');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('sub_targets');
    }
};
