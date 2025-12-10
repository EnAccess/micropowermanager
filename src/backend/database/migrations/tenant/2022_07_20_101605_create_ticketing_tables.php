<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketingTables extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $tableNames = [
            'board' => 'ticket_boards',
            'board_categories' => 'ticket_board_categories',
            'card' => 'ticket_cards',
            'ticket' => 'tickets',
            'user' => 'ticket_users',
            'ticket_categories' => 'ticket_categories',
            'ticket_outsource' => 'ticket_outsources',
            'outsource_reports' => 'ticket_outsource_reports',
            'ticket_comments' => 'ticket_comments',
        ];

        Schema::connection('tenant')->create($tableNames['ticket'], function (Blueprint $table) {
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

        Schema::connection('tenant')->create($tableNames['user'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_name')->unique();
            $table->string('phone')->nullable();
            $table->integer('out_source');
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create($tableNames['ticket_categories'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('label_name');
            $table->string('label_color');
            $table->boolean('out_source');
            $table->timestamps();
        });

        Schema::connection('tenant')->create($tableNames['outsource_reports'], static function (Blueprint $table) {
            $table->increments('id');
            $table->string('date');
            $table->string('path');
            $table->timestamps();
        });

        Schema::connection('tenant')->create($tableNames['ticket_outsource'], static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id');
            $table->integer('amount');
            $table->timestamps();
        });
        Schema::connection('tenant')->create($tableNames['ticket_comments'], static function (Blueprint $table) {
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
    public function down() {
        $tableNames = [
            'board' => 'ticket_boards',
            'board_categories' => 'ticket_board_categories',
            'card' => 'ticket_cards',
            'ticket' => 'tickets',
            'user' => 'ticket_users',
            'ticket_categories' => 'ticket_categories',
            'ticket_outsource' => 'ticket_outsources',
            'outsource_reports' => 'ticket_outsource_reports',
            'ticket_comments' => 'ticket_comments',
        ];

        Schema::connection('tenant')->drop($tableNames['ticket']);
        Schema::connection('tenant')->drop($tableNames['user']);
        Schema::connection('tenant')->drop($tableNames['ticket_categories']);
        Schema::connection('tenant')->drop($tableNames['outsource_reports']);
        Schema::connection('tenant')->drop($tableNames['ticket_outsource']);
        Schema::connection('tenant')->drop($tableNames['ticket_comments']);
    }
}
