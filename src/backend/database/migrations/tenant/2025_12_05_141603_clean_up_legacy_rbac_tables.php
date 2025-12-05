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
    public function up(): void {
        if (Schema::connection('tenant')->hasTable('role_definitions')) {
            Schema::connection('tenant')->dropIfExists('role_definitions');
        }

        if (Schema::connection('tenant')->hasTable('roles')) {
            Schema::connection('tenant')->dropIfExists('roles');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('role_definitions')) {
            Schema::connection('tenant')->create('role_definitions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('role_name');
            });
        }

        if (!Schema::connection('tenant')->hasTable('roles')) {
            Schema::connection('tenant')->create('roles', function (Blueprint $table) {
                $table->increments('id');
                $table->morphs('role_owner');
                $table->integer('role_definition_id');
                $table->timestamps();
            });
        }
    }
};
