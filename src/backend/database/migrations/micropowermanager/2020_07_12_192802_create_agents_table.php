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
        Schema::connection('tenant')->create('agents', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('person_id');
            $table->unsignedInteger('mini_grid_id');
            $table->unsignedInteger('agent_commission_id');
            $table->string('device_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('fire_base_token');
            $table->double('balance')->default(0);
            $table->double('commission_revenue')->default(0);
            $table->double('due_to_energy_supplier')->default(0);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('agents');
    }
};
