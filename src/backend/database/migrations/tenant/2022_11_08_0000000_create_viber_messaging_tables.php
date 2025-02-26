<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('viber_credentials')) {
            Schema::connection('tenant')->create('viber_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_token')->nullable();
                $table->string('webhook_url')->nullable();
                $table->boolean('has_webhook_created')->default(false);
                $table->string('deep_link')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('viber_messages')) {
            Schema::connection('tenant')->create('viber_messages', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sms_id');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('viber_contacts')) {
            Schema::connection('tenant')->create('viber_contacts', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('person_id');
                $table->string('viber_id');
                $table->string('registered_meter_serial_number');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('viber_credentials');
        Schema::connection('tenant')->dropIfExists('viber_messages');
        Schema::connection('tenant')->dropIfExists('viber_contacts');
    }
};
