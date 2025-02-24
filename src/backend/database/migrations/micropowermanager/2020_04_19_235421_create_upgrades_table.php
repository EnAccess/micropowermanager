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
        Schema::connection('tenant')->create('upgrades', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('restriction_id');
            $table->integer('cost'); // 100 times the price to handle two digit floating numbers
            $table->integer('amount');
            $table->integer('period_in_months');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('upgrades');
    }

    // TODO : add to seeders
    public function addDefault() {
        DB::table('upgrades')->insert([
            'restriction_id' => 1,
            'cost' => 36000,
            'amount' => 1,
            'period_in_months' => 12,
        ]);

        DB::table('upgrades')->insert([
            'restriction_id' => 2,
            'cost' => 12000,
            'amount' => 1,
            'period_in_months' => 12,
        ]);
        DB::table('upgrades')->insert([
            'restriction_id' => 2,
            'cost' => 60000,
            'amount' => 5,
            'period_in_months' => 12,
        ]);
    }
};
