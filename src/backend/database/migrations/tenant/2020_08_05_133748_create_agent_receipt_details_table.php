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
        Schema::connection('tenant')->create('agent_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->integer('agent_receipt_id');
            $table->double('due')->default(0);
            $table->double('since_last_visit')->default(0);
            $table->double('earlier')->default(0);
            $table->double('collected')->default(0);
            $table->double('summary')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('agent_receipt_details');
    }
};
