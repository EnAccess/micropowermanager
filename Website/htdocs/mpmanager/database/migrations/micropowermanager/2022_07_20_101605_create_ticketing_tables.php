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
class CreateTicketingTables extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('tickets.table_names');

        Schema::connection('shard')->create($tableNames['ticket'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('ticket_id');
            $table->morphs('creator');
            $table->integer('assigned_id')->nullable();
            $table->morphs('owner');
            $table->integer('status');
            $table->timestamp('due_date')->nullable();
            $table->string('title');
            $table->text('content');
            $table->integer('category_id');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['user'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_name')->unique();
            $table->string('phone')->nullable();
            $table->integer('out_source');
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['ticket_categories'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('label_name');
            $table->string('label_color');
            $table->boolean('out_source');
            $table->timestamps();
        });

        Schema::connection('shard')->create($tableNames['outsource_reports'], static function(Blueprint $table){
            $table->increments('id');
            $table->string('date');
            $table->string('path');
            $table->timestamps();
        });

         Schema::connection('shard')->create($tableNames['ticket_outsource'], static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id');
            $table->integer('amount');
            $table->timestamps();
        });
         Schema::connection('shard')->create($tableNames['ticket_comments'], static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id');
            $table->integer('ticket_user_id');
            $table->text('comment');
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
        Schema::connection('shard')->drop($tableNames['ticket']);
        Schema::connection('shard')->drop($tableNames['user']);
        Schema::connection('shard')->drop($tableNames['ticket_categories']);
        Schema::connection('shard')->drop($tableNames['ticket_outsource']);

    }

}
