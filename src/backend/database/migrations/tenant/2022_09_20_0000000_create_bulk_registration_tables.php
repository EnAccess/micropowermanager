<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        if (!Schema::connection('tenant')->hasTable('bulk_registration_csv_datas')) {
            Schema::connection('tenant')->create('bulk_registration_csv_datas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('csv_filename');
                $table->json('csv_data');
                $table->timestamps();
            });
        }
    }

    public function down() {
        Schema::connection('tenant')->dropIfExists('bulk_registration_csv_datas');
    }
};
