<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketingTablesOutsource extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = config('tickets.table_names');


        Schema::create($tableNames['ticket_outsource'], static function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ticket_id');
            $table->integer('amount');
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
        $tableNames = config('tickets.table_names');

        Schema::drop($tableNames['ticket_outsource']);
    }
}
