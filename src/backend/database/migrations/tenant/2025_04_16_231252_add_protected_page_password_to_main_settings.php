<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        $existingPasswords = DB::connection('micro_power_manager')->table('companies')
            ->whereNotNull('protected_page_password')
            ->pluck('protected_page_password', 'name');

        foreach ($existingPasswords as $companyName => $password) {
            DB::connection('tenant')
                ->table('main_settings')
                ->where('company_name', $companyName)
                ->update(['protected_page_password' => $password]);
        }
        Schema::connection('micro_power_manager')->table('companies', function (Blueprint $table) {
            $table->dropColumn('protected_page_password');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::connection('micro_power_manager')->table('companies', function (Blueprint $table) {
            $table->string('protected_page_password')->nullable();
        });
        $mainSettingsPasswords = DB::connection('tenant')
            ->table('main_settings')
            ->whereNotNull('protected_page_password')
            ->pluck('protected_page_password', 'name');

        foreach ($mainSettingsPasswords as $companyName => $password) {
            DB::connection('micro_power_manager')->table('companies')
                ->where('name', $companyName)
                ->update(['protected_page_password' => $password]);
        }
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->dropColumn('protected_page_password');
        });
    }
};
