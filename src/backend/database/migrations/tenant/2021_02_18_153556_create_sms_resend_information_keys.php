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
        Schema::connection('tenant')->create('sms_resend_information_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->default('Resend');
            $table->timestamps();
        });

        DB::connection('tenant')->table('sms_resend_information_keys')->insert([
            'key' => 'Resend',
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
        Schema::connection('tenant')->dropIfExists('sms_resend_information_keys');
    }
};
