<?php

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
        Schema::connection('tenant')->create('sub_menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 50);
            $table->char('url_slug', 50);
            $table->integer('parent_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('sub_menu_items');
    }
};
