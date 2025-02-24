<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::hasTable('swifta_transactions')) {
            Schema::connection('tenant')->create('swifta_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('transaction_reference')->nullable();
                $table->string('manufacturer_transaction_type')->nullable();
                $table->integer('manufacturer_transaction_id')->nullable();
                $table->integer('status')->default(-2);
                $table->decimal('amount');
                $table->string('cipher');
                $table->string('timestamp');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('swifta_authentication')) {
            Schema::connection('tenant')->create('swifta_authentication', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('token')->nullable();
                $table->unsignedInteger('expire_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('swifta_transactions');
        Schema::connection('tenant')->dropIfExists('swifta_authentication');
    }
};
