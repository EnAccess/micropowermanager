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
        Schema::connection('tenant')->table('meter_tariffs', function (Blueprint $table) {
            $table->double('minimum_purchase_amount')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('meter_tariffs', function (Blueprint $table) {
            $table->dropColumn('minimum_purchase_amount');
        });
    }
};
