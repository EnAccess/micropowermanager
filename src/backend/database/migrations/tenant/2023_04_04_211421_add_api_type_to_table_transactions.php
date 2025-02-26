<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('energy','deferred_payment','unknown','imported','3rd party api sync')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('energy','deferred_payment','unknown')");
    }
};
