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
            $table->string('uuid', 50)->nullable()->change();
            $table->dropColumn('attempts');
            $table->dropColumn('dispatched');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sms', function (Blueprint $table) {
            $table->string('uuid')->change();
            $table->integer('attempts')->default(0);
            $table->integer('dispatched')->default(-1);
        });
    }
};
