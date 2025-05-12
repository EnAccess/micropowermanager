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
            $table->integer('token_amount')->nullable();
            $table->string('token_type')->nullable();
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
            $table->dropColumn('token_amount');
            $table->dropColumn('token_type');
        });
    }
};
