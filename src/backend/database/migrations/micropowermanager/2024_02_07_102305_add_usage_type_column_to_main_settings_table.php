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
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->enum('usage_type', [
                'mini-grid',
                'shs',
                'e-bike',
                'mini-grid&shs',
                'mini-grid&e-bike',
                'shs&e-bike',
                'mini-grid&shs&e-bike',
            ])->default('mini-grid&shs&e-bike')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {});
    }
};
