<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->enum('usage_type', [
                'mini-grid',
                'shs',
                'e-bike',
                'general',
            ])->default('general')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mpm_plugins', function (Blueprint $table) {
        });
    }
};
