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
        Schema::connection('tenant')->dropIfExists('solars');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('solars', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('mini_grid_id');
            $table->integer('node_id');
            $table->string('device_id');
            $table->timestamp('time_stamp');
            $table->bigInteger('starting_time');
            $table->bigInteger('ending_time');
            $table->integer('min');
            $table->integer('max');
            $table->integer('average');
            $table->integer('duration');
            $table->integer('readings');
            $table->timestamps();
        });

        Schema::connection('tenant')->table('solars', function (Blueprint $table) {
            $table->integer('frequency')->nullable();
            $table->double('pv_power')->nullable();
            $table->double('fraction')->default(0);
            $table->string('storage_file_name')->nullable();
        });

        Schema::connection('tenant')->table('solars', function (Blueprint $table) {
            $table->renameColumn('storage_file_name', 'storage_folder')->change();
        });
    }
};
