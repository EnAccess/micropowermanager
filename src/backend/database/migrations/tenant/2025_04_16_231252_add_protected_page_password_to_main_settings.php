<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->longText('protected_page_password')->nullable();
        });

        if (Schema::connection('micro_power_manager')->hasTable('companies') && Schema::connection('micro_power_manager')->hasColumn('companies', 'protected_page_password')) {
            // Add the deprecation comment to existing column
            DB::connection('micro_power_manager')->statement(
                "ALTER TABLE companies MODIFY COLUMN protected_page_password VARCHAR(255) NULL COMMENT 'DEPRECATED: Use main_settings.protected_page_password in tenant database instead'"
            );
            // Copy existing data from companies to main_settings if it exists
            $existingPasswords = DB::connection('micro_power_manager')->table('companies')
                ->whereNotNull('protected_page_password')
                ->pluck('protected_page_password', 'name');
        } else {
            $existingPasswords = collect();
        }

        foreach ($existingPasswords as $companyName => $password) {
            DB::connection('tenant')
                ->table('main_settings')
                ->where('company_name', $companyName)
                ->update(['protected_page_password' => Crypt::encrypt($password)]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Remove the column from main_settings
        if (Schema::connection('tenant')->hasColumn('main_settings', 'protected_page_password')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
                $table->dropColumn('protected_page_password');
            });
        }

        // Remove the deprecation comment from companies table
        if (Schema::connection('micro_power_manager')->hasTable('companies') && Schema::connection('micro_power_manager')->hasColumn('companies', 'protected_page_password')) {
            DB::connection('micro_power_manager')->statement(
                'ALTER TABLE companies MODIFY COLUMN protected_page_password VARCHAR(255) NULL'
            );
        }
    }
};
