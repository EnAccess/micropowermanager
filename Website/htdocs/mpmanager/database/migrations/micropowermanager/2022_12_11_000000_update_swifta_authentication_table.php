<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\Type;

return  new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('token')) {
            Type::addType('token', FloatType::class);
        }
        Schema::connection('shard')->table('swifta_authentication', function (Blueprint $table) {
            $table->text('token')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('shard')->table('swifta_authentication', function (Blueprint $table) {
            //
        });
    }
};
