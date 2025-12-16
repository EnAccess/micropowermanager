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
            $table->renameColumn('sex', 'gender');
            $table->string('gender', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('people', function (Blueprint $table) {
            $table->string('gender', 6)->nullable()->change();
            $table->renameColumn('gender', 'sex');
        });
    }
};
