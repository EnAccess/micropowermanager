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
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->unsignedInteger('sms_gateway_id')->nullable()->comment('Selected SMS gateway ID: 2=AndroidGateway, 10=ViberGateway, 18=AfricasTalkingGateway, 26=TextbeeSmsGateway');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::connection('tenant')->hasColumn('main_settings', 'sms_gateway_id')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
                $table->dropColumn('sms_gateway_id');
            });
        }
    }
};
