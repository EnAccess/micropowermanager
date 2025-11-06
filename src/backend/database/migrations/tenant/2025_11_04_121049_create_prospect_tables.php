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
                $table->string('api_url', 500)->nullable();
                $table->string('api_token', 500)->nullable();
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

        if (!Schema::connection('tenant')->hasTable('prospect_extracted_files')) {
            Schema::connection('tenant')->create('prospect_extracted_files', static function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('file_path')->nullable();
                $table->integer('records_count')->default(0);
                $table->integer('file_size')->nullable();
                $table->timestamp('extracted_at')->nullable();
                $table->boolean('is_synced')->default(false);
                $table->timestamp('synced_at')->nullable();
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
        Schema::connection('tenant')->dropIfExists('prospect_credentials');
        Schema::connection('tenant')->dropIfExists('prospect_sync_actions');
        Schema::connection('tenant')->dropIfExists('prospect_sync_settings');
        Schema::connection('tenant')->dropIfExists('prospect_extracted_files');
    }
};
