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
        Schema::connection('tenant')->table('agent_sold_appliances', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'down_payment')) {
                $table->double('down_payment')->nullable();
            }

            if (!Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'tenure')) {
                $table->integer('tenure');
            }

            if (!Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'first_payment_date')) {
                $table->date('first_payment_date')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('agent_sold_appliances', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'down_payment')) {
                $table->dropColumn('down_payment');
            }

            if (Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'tenure')) {
                $table->dropColumn('tenure');
            }

            if (Schema::connection('tenant')->hasColumn('agent_sold_appliances', 'first_payment_date')) {
                $table->dropColumn('first_payment_date');
            }
        });
    }
};
