<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRestrictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restrictions', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('target');
            $table->integer('default');
            $table->integer('limit');
            $table->timestamps();
        });
        $this->addDefault();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restrictions');
    }

    public function addDefault()
    {
        $timeStamp = \Carbon\Carbon::now();

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
}
