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
        Schema::connection('tenant')->table('solars', function (Blueprint $table) {
            $table->integer('frequency')->nullable();
            $table->double('pv_power')->nullable();
            $table->double('fraction')->default(0);
            $table->string('storage_file_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('solars', function (Blueprint $table) {
            $table->dropColumn('frequency');
            $table->dropColumn('pv_power');
            $table->dropColumn('fraction');
            $table->dropColumn('storage_file_name');
        });
    }
};
