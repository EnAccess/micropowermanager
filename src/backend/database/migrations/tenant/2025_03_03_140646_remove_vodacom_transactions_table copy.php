<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->dropIfExists('vodacom_transactions');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
    }
};
