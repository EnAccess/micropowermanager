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
            $table->renameColumn('storage_file_name', 'storage_folder')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('solars', function (Blueprint $table) {
            $table->renameColumn('storage_folder', 'storage_file_name')->change();
        });
    }
};
