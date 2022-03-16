<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


class CreateOutsourceReport extends Migration{

    public function up(){

        $tableNames = config('tickets.table_names');

        Schema::create($tableNames['outsource_reports'], static function(Blueprint $table){
            $table->increments('id');
            $table->string('date');
            $table->string('path');
            $table->timestamps();
        });
        }


    public function down(){
        $tableNames = config('tickets.table_names');
        Schema::drop($tableNames['outsource_reports']);
    }

}
