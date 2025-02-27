<?php

use Carbon\Carbon;
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
    public function up(): void {
        Schema::connection('tenant')->create('sub_connection_types', static function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('connection_type_id');
            $table->integer('tariff_id');
            $table->timestamps();
        });

        DB::connection('tenant')->table('sub_connection_types')->insert([
            'name' => 'default  sub connection type',
            'tariff_id' => 1,
            'connection_type_id' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::connection('tenant')->dropIfExists('sub_connection_types');
    }
};
