<?php

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
        Schema::connection('tenant')->create('restrictions', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target');
            $table->integer('default');
            $table->integer('limit');
            $table->timestamps();
        });
        //   $this->addDefault();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('restrictions');
    }

    public function addDefault() {
        $timeStamp = Carbon\Carbon::now();

        DB::table('restrictions')->insert([
            'target' => 'enable-data-stream',
            'default' => '5',
            'limit' => '5',
            'created_at' => $timeStamp,
            'updated_at' => $timeStamp,
        ]);
        DB::table('restrictions')->insert([
            'target' => 'maintenance-user',
            'default' => '5',
            'limit' => '5',
            'created_at' => $timeStamp,
            'updated_at' => $timeStamp,
        ]);
    }
};
