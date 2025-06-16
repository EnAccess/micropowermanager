<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('agent_charges', function (Blueprint $table) {
            $table->double('amount')->change();
        });

        // For MySQL Laravel's `float` gets translated to DOUBLE(8,2).
        // As a result changing it to `double` using Laravel might not have an effect.
        // Running a SQL statement here to be sure.
        DB::connection('tenant')->statement('ALTER TABLE agent_charges MODIFY amount DOUBLE NOT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('agent_charges', function (Blueprint $table) {
            $table->float('amount')->change();
        });

        DB::connection('tenant')->statement('ALTER TABLE agent_charges MODIFY amount DOUBLE(8,2) NOT NULL');
    }
};
