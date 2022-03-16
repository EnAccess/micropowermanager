<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenuItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name',50);
            $table->char('url_slug',50);
            $table->char('md_icon',50);
            $table->unsignedInteger('menu_order')->default(999);
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
        Schema::dropIfExists('menu_items');
    }
}
