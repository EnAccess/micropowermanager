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
        Schema::connection('tenant')->table('meter_tariffs', function (Blueprint $table) {
            $table->double('price', 15, 6)->change();
            $table->double('total_price', 15, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('meter_tariffs', function (Blueprint $table) {});
    }
};
