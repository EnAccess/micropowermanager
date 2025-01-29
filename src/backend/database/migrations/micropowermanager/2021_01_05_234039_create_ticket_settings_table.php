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
    public function up() {
        Schema::connection('tenant')->create('ticket_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('api_token');
            $table->string('api_url');
            $table->string('api_key');
            $table->timestamps();
        });

        DB::connection('tenant')->table('ticket_settings')->insert([
            'name' => 'Trello',
            'api_token' => '----',
            'api_url' => 'https://api.trello.com/1',
            'api_key' => '----',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('ticket_settings');
    }
};
