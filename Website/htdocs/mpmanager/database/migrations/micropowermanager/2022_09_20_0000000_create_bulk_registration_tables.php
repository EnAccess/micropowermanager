<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class  extends Migration
{
    public function up()
    {
        if (!Schema:: hasTable('bulk_registration_csv_datas')) {
            Schema::connection('shard')->create('bulk_registration_csv_datas', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->string('csv_filename');
                $table->json('csv_data');
                $table->timestamps();
            });
        }
    }
    public function down()
    {
        Schema::connection('micropowermanager')->dropIfExists('bulk_registration_csv_datas');
    }
};
