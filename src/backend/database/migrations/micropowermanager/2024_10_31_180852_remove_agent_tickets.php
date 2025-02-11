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
        Schema::connection('tenant')->dropIfExists('agent_tickets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // This table never had any migration files and hence never existed
        // on fresh installations of MPM.
    }
};
