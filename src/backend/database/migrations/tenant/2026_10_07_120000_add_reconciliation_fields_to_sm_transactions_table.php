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
        Schema::connection('tenant')->table('sm_transactions', function (Blueprint $table) {
            $table->decimal('amount')->nullable();
            $table->string('source')->nullable();
            $table->string('memo')->nullable();
            $table->string('type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sm_transactions', function (Blueprint $table) {
            $table->dropColumn(['amount', 'source', 'memo', 'type']);
        });
    }
};
