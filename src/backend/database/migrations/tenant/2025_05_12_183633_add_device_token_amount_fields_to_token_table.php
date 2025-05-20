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
        Schema::connection('tenant')->table('tokens', function (Blueprint $table) {
            $table->integer('device_id')->nullable();
            $table->string('token_type')->nullable();
            $table->string('token_unit')->nullable();
            $table->renameColumn('load', 'token_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('tokens', function (Blueprint $table) {
            $table->dropColumn('device_id');
            $table->dropColumn('token_type');
            $table->dropColumn('token_unit');
            $table->renameColumn('token_amount', 'load');
        });
    }
};
