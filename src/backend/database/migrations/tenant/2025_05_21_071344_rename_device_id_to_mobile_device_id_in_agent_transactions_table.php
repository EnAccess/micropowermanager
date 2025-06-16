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
        Schema::connection('tenant')->table('agent_transactions', function (Blueprint $table) {
            $table->renameColumn('device_id', 'mobile_device_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('agent_transactions', function (Blueprint $table) {
            $table->renameColumn('mobile_device_id', 'device_id');
        });
    }
};
