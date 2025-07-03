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
        if (Schema::connection('micro_power_manager')->hasColumn('companies', 'protected_page_password')) {
            $existingPasswords = DB::connection('micro_power_manager')->table('companies')
                ->whereNotNull('protected_page_password')
                ->pluck('protected_page_password', 'name');
        } else {
            $existingPasswords = collect();
        }

        Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
            $table->longText('protected_page_password')->nullable();
        });

        foreach ($existingPasswords as $companyName => $password) {
            DB::connection('tenant')
                ->table('main_settings')
                ->where('company_name', $companyName)
                ->update(['protected_page_password' => Crypt::encrypt($password)]);
        }

        if (Schema::connection('micro_power_manager')->hasColumn('companies', 'protected_page_password')) {
            Schema::connection('micro_power_manager')->table('companies', function (Blueprint $table) {
                $table->dropColumn('protected_page_password');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        if (Schema::connection('micro_power_manager')->hasTable('companies') && !Schema::connection('micro_power_manager')->hasColumn('companies', 'protected_page_password')) {
            Schema::connection('micro_power_manager')->table('companies', function (Blueprint $table) {
                $table->string('protected_page_password')->nullable();
            });

            $mainSettingsPasswords = DB::connection('tenant')
                ->table('main_settings')
                ->whereNotNull('protected_page_password')
                ->pluck('protected_page_password', 'company_name');

            foreach ($mainSettingsPasswords as $companyName => $password) {
                DB::connection('micro_power_manager')->table('companies')
                    ->where('name', $companyName)
                    ->update(['protected_page_password' => Crypt::decrypt($password)]);
            }
        }
        if (Schema::connection('tenant')->hasColumn('main_settings', 'protected_page_password')) {
            Schema::connection('tenant')->table('main_settings', function (Blueprint $table) {
                $table->dropColumn('protected_page_password');
            });
        }
    }
};
