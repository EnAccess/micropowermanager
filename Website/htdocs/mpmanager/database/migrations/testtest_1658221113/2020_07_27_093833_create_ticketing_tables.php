<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 10:39
 */
return new class extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('tickets.table_names');

        Schema::connection('shard')->create($tableNames['board'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('board_id');
            $table->string('board_name');
            $table->string('web_hook_id');
            $table->boolean('active');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['card'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_id');
            $table->integer('status');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['ticket'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('ticket_id');
            $table->morphs('creator');
            $table->integer('assigned_id')->nullable();
            $table->morphs('owner');
            $table->integer('status');
            $table->integer('category_id');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['user'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_name');
            $table->string('user_tag');
            $table->integer('out_source');
            $table->string('extern_id');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['ticket_categories'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('label_name');
            $table->string('label_color');
            $table->boolean('out_source');
            $table->timestamps();
        });
        Schema::connection('shard')->create($tableNames['board_categories'], function (Blueprint $table) {
            $table->increments('id');
            $table->integer('category_id');
            $table->integer('board_id');
            $table->string('extern_category_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('ticket.table_names');
        Schema::connection('shard')->drop($tableNames['board']);
        Schema::connection('shard')->drop($tableNames['card']);
        Schema::connection('shard')->drop($tableNames['ticket']);
        Schema::connection('shard')->drop($tableNames['user']);
        Schema::connection('shard')->drop($tableNames['ticket_categories']);
        Schema::connection('shard')->drop($tableNames['board_categories']);
    }

};
