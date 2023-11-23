<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {

    public function up()
    {
        if (!Schema:: hasTable('micro_star_api_credentials')) {
            Schema::connection('shard')->create('micro_star_api_credentials', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('api_url')->nullable();
                $table->string('certificate_file_name')->nullable();
                $table->string('certificate_path')->nullable();
                $table->string('certificate_password')->nullable();

                $table->timestamps();
            });
        }
        if (!Schema:: hasTable('micro_star_transactions')) {
            Schema::connection('shard')->create('micro_star_transactions', static function (Blueprint $table) {
                $table->increments('id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::connection('shard')->dropIfExists('micro_star_api_credentials');
        Schema::connection('shard')->dropIfExists('micro_star_transactions');

    }
};
