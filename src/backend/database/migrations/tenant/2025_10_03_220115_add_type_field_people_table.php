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
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->enum('type', ['maintenance', 'customer', 'agent'])->default('customer')->after('is_customer');
            $table->integer('mini_grid_id')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('mini_grid_id');
        });
    }
};
