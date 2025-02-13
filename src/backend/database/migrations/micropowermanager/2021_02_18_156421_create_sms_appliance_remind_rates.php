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
        Schema::connection('tenant')->create('sms_appliance_remind_rates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('appliance_type_id');
            $table->integer('overdue_remind_rate');
            $table->integer('remind_rate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('sms_appliance_remind_rates');
    }
};
