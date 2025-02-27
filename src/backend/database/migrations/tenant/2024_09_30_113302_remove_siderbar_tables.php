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
        Schema::connection('tenant')->dropIfExists('menu_items');
        Schema::connection('tenant')->dropIfExists('sub_menu_items');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->create('menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('usage_type', 191)->default('general');
            $table->char('name', 50);
            $table->char('url_slug', 50);
            $table->char('md_icon', 50);
            $table->unsignedInteger('menu_order')->default(999);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::connection('tenant')->create('sub_menu_items', function (Blueprint $table) {
            $table->increments('id');
            $table->char('name', 50);
            $table->char('url_slug', 50);
            $table->integer('parent_id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }
};
