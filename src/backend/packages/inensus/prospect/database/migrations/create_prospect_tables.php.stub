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
        if (!Schema::connection('tenant')->hasTable('prospect_credentials')) {
            Schema::connection('tenant')->create('prospect_credentials', static function (Blueprint $table) {
                $table->id();
                $table->string('api_url')->default('https://demo.prospect.energy/api/v1/in/installations');
                $table->string('api_token')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('prospect_data_syncs')) {
            Schema::connection('tenant')->create('prospect_data_syncs', static function (Blueprint $table) {
                $table->id();
                $table->string('sync_type'); // 'installations', 'payments'
                $table->integer('total_records')->default(0);
                $table->integer('synced_records')->default(0);
                $table->integer('failed_records')->default(0);
                $table->timestamp('last_sync_at')->nullable();
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->dropIfExists('prospect_data_syncs');
        Schema::connection('tenant')->dropIfExists('prospect_credentials');
    }
};
