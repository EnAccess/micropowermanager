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
        Schema::connection('tenant')->table('mini_grids', function (Blueprint $table) {
            $table->dropColumn('data_stream');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('mini_grids', function (Blueprint $table) {
            $table->integer('data_stream')->default(0)->after('name');
        });
    }
};
