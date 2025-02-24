<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('wave_money_transactions')) {
            Schema::connection('tenant')->create('wave_money_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('status')->default(-2);
                $table->decimal('amount');
                $table->text('order_id');
                $table->text('reference_id');
                $table->text('currency');
                $table->integer('customer_id');
                $table->integer('meter_serial')->nullable();
                $table->string('external_transaction_id')->nullable();
                $table->integer('attempts')->default(0);
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('wave_money_credentials')) {
            Schema::connection('tenant')->create('wave_money_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('merchant_id')->nullable();
                $table->string('merchant_name')->nullable();
                $table->string('secret_key')->nullable();
                $table->string('callback_url')->nullable();
                $table->string('payment_url')->nullable();
                $table->string('result_url')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('wave_money_transactions');
        Schema::connection('tenant')->dropIfExists('wave_money_credentials');
    }
};
