<?php

use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;
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
        if (!Type::hasType('double')) {
            Type::addType('double', FloatType::class);
        }
        Schema::connection('tenant')->table('access_rate_payments', function (Blueprint $table) {
            $table->double('debt')->change();
            $table->double('unpaid_in_row')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('access_rate_payments', function (Blueprint $table) {});
    }
};
