<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {

        if (!Schema::hasTable('vodacom_mobile_money_transactions')) {
            Schema::create('vodacom_mobile_money_transactions', static function (Blueprint $table) {
                $table->id();
                $table->string('serialNumber');
                $table->decimal('amount', 15, 2);
                $table->string('payerPhoneNumber');
                $table->string('referenceId')->unique();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('vodacom_mobile_money_transactions');
    }
};