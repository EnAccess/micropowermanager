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
        if (Schema::connection('tenant')->hasTable('customers')) {
            Schema::connection('tenant')->dropIfExists('customers');
        }
        if (Schema::connection('tenant')->hasTable('customer_groups')) {
            Schema::connection('tenant')->dropIfExists('customer_groups');
        }
    }

    public function down(): void {
        if (!Schema::connection('tenant')->hasTable('customers')) {
            Schema::connection('tenant')->create('customers', function (Blueprint $table) {
                $table->increments('id');
                $table->morphs('role_owner');
                $table->timestamps();
            });
        }
        if (!Schema::connection('tenant')->hasTable('customer_groups')) {
            Schema::connection('tenant')->create('customer_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('tariff_id');
                $table->string('name');
                $table->timestamps();
            });
        }
    }
};
