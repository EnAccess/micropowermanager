<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('shard')->create('mail_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mail_host');
            $table->integer('mail_port');
            $table->char('mail_encryption', 10);
            $table->string('mail_username');
            $table->string('mail_password');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->dropIfExists('mail_settings');
    }
};
