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
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->integer('status')->default(0)->change();
            $table->integer('sender_id')->nullable()->change();
            $table->integer('trigger_id')->nullable()->change();
            $table->integer('trigger_type')->nullable()->change();
            $table->integer('uuid')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {});
    }
};
