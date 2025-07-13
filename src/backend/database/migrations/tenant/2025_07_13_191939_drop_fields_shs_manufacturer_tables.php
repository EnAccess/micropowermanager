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
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount']);
        });

        Schema::table('sun_king_transactions', function (Blueprint $table) {
            $table->dropColumn(['status', 'amount']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::table('angaza_transactions', function (Blueprint $table) {
            $table->integer('status')->default(-1)->after('id');
            $table->decimal('amount', 10, 2)->after('status');
        });

        Schema::table('sun_king_transactions', function (Blueprint $table) {
            $table->integer('status')->default(-1)->after('id');
            $table->decimal('amount', 10, 2)->after('status');
        });
    }
};
