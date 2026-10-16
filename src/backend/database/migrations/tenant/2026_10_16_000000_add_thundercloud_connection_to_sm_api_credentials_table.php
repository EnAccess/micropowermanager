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
        Schema::connection('tenant')->table('sm_api_credentials', function (Blueprint $table) {
            $table->string('thundercloud_url')->nullable()->after('api_secret');
            $table->string('thundercloud_token')->nullable()->after('thundercloud_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('tenant')->table('sm_api_credentials', function (Blueprint $table) {
            $table->dropColumn(['thundercloud_url', 'thundercloud_token']);
        });
    }
};
