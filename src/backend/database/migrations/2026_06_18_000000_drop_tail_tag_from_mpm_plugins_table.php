<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->dropColumn('tail_tag');
        });
    }

    public function down() {
        Schema::table('mpm_plugins', function (Blueprint $table) {
            $table->string('tail_tag')->nullable();
        });
    }
};
