<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up() : void
    {
        if (!Schema::connection('tenant')->hasTable('demo_meter_transactions')) {
            Schema::connection('tenant')->create('demo_meter_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down() : void
    {
        Schema::connection('tenant')->dropIfExists('demo_meter_transactions');
    }
};
