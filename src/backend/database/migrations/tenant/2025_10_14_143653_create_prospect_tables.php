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

        if (!Schema::connection('tenant')->hasTable('prospect_sync_settings')) {
            Schema::connection('tenant')->create('prospect_sync_settings', static function (Blueprint $table) {
                $table->id();
                $table->string('action_name')->unique();
                $table->string('sync_in_value_str');
                $table->integer('sync_in_value_num')->default(1);
                $table->integer('max_attempts')->default(3);
                $table->timestamps();
            });
        }

        if (!Schema::connection('tenant')->hasTable('prospect_sync_actions')) {
            Schema::connection('tenant')->create('prospect_sync_actions', static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sync_setting_id');
                $table->integer('attempts')->default(0);
                $table->dateTime('last_sync')->nullable();
                $table->dateTime('next_sync')->nullable();
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
        Schema::connection('tenant')->dropIfExists('prospect_sync_actions');
        Schema::connection('tenant')->dropIfExists('prospect_sync_settings');
    }
};
