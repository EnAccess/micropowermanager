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
        if (!Schema::hasTable('textbee_credentials')) {
            Schema::connection('tenant')->create('textbee_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->text('api_key')->nullable();
                $table->text('device_id')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('textbee_messages')) {
            Schema::connection('tenant')->create('textbee_messages', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sms_id');
                $table->string('message_id');
                $table->string('status');
                $table->string('created_at_textbee')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('textbee_credentials');
        Schema::connection('tenant')->dropIfExists('textbee_messages');
    }
};
