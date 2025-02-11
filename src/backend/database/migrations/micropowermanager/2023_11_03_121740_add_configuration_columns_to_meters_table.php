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
        Schema::connection('tenant')->table('meters', function (Blueprint $table) {
            $table->integer('connection_type_id');
            $table->integer('connection_group_id');
            $table->integer('tariff_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('meters', function (Blueprint $table) {
            $table->dropColumn('connection_type_id');
            $table->dropColumn('connection_group_id');
            $table->dropColumn('tariff_id');
        });
    }
};
