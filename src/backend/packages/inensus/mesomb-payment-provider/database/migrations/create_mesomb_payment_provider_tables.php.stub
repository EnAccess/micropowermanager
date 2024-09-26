<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {

        if (!Schema:: hasTable('mesomb_transactions')) {
            Schema::create('mesomb_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('pk');
                $table->integer('status')->default(0);
                $table->string('type');
                $table->decimal('amount');
                $table->decimal('fees')->nullable();
                $table->string('b_party');
                $table->string('message');
                $table->string('service');
                $table->string('reference')->nullable();
                $table->string('ts');
                $table->integer('direction');
                $table->timestamps();
            });
        }


    }

    public function down()
    {
        Schema::dropIfExists('mesomb_transactions');
    }
};