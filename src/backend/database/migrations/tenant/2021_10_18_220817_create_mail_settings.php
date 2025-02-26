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
        Schema::connection('tenant')->create('mail_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mail_host');
            $table->integer('mail_port');
            $table->char('mail_encryption', 10);
            $table->string('mail_username');
            $table->string('mail_password');
            $table->timestamps();
        });

        DB::connection('tenant')->table('mail_settings')->insert([
            'mail_host' => 'smtp.example.com',
            'mail_port' => 123,
            'mail_encryption' => 'tls',
            'mail_username' => 'example@domain.com',
            'mail_password' => '123123',
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
        Schema::connection('tenant')->dropIfExists('mail_settings');
    }
};
