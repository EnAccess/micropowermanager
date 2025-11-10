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
        // Define credential tables and their encrypted fields that need to be changed to text
        $credentialTables = [
            'africas_talking_credentials' => ['api_key', 'username', 'short_code'],
            'angaza_api_credentials' => ['client_id', 'client_secret'],
            'calin_api_credentials' => ['user_id', 'api_key'],
            'calin_smart_api_credentials' => ['company_name', 'user_name', 'password', 'password_vend'],
            'chint_api_credentials' => ['user_name', 'user_password'],
            'daly_bms_api_credentials' => ['user_name', 'password', 'access_token'],
            'kelin_api_credentials' => ['username', 'password', 'authentication_token'],
            'micro_star_api_credentials' => ['certificate_file_name', 'certificate_path', 'certificate_password'],
            'sm_api_credentials' => ['api_key', 'api_secret'],
            'steama_credentials' => ['username', 'password', 'authentication_token'],
            'stron_api_credentials' => ['api_token', 'company_name', 'username', 'password'],
            'sun_king_api_credentials' => ['client_id', 'client_secret', 'access_token'],
            'viber_credentials' => ['api_token', 'webhook_url', 'deep_link'],
            'wave_money_credentials' => ['merchant_id', 'merchant_name', 'secret_key', 'callback_url', 'payment_url', 'result_url'],
            'gome_long_api_credentials' => ['user_id', 'user_password'],
            'prospect_credentials' => ['api_token', 'api_url'],
        ];

        foreach ($credentialTables as $tableName => $fields) {
            if (Schema::connection('tenant')->hasTable($tableName)) {
                Schema::connection('tenant')->table($tableName, function (Blueprint $table) use ($fields) {
                    foreach ($fields as $field) {
                        if (Schema::connection('tenant')->hasColumn($table->getTable(), $field)) {
                            $table->text($field)->nullable()->change();
                        }
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Define credential tables and their encrypted fields to revert to string(500)
        $credentialTables = [
            'africas_talking_credentials' => ['api_key', 'username', 'short_code'],
            'angaza_api_credentials' => ['client_id', 'client_secret'],
            'calin_api_credentials' => ['user_id', 'api_key'],
            'calin_smart_api_credentials' => ['company_name', 'user_name', 'password', 'password_vend'],
            'chint_api_credentials' => ['user_name', 'user_password'],
            'daly_bms_api_credentials' => ['user_name', 'password', 'access_token'],
            'kelin_api_credentials' => ['username', 'password', 'authentication_token'],
            'micro_star_api_credentials' => ['certificate_file_name', 'certificate_path', 'certificate_password'],
            'sm_api_credentials' => ['api_key', 'api_secret'],
            'steama_credentials' => ['username', 'password', 'authentication_token'],
            'stron_api_credentials' => ['api_token', 'company_name', 'username', 'password'],
            'sun_king_api_credentials' => ['client_id', 'client_secret', 'access_token'],
            'viber_credentials' => ['api_token', 'webhook_url', 'deep_link'],
            'wave_money_credentials' => ['merchant_id', 'merchant_name', 'secret_key', 'callback_url', 'payment_url', 'result_url'],
            'gome_long_api_credentials' => ['user_id', 'user_password'],
        ];

        foreach ($credentialTables as $tableName => $fields) {
            if (Schema::connection('tenant')->hasTable($tableName)) {
                Schema::connection('tenant')->table($tableName, function (Blueprint $table) use ($fields) {
                    foreach ($fields as $field) {
                        if (Schema::connection('tenant')->hasColumn($table->getTable(), $field)) {
                            $table->string($field, 500)->nullable()->change();
                        }
                    }
                });
            }
        }
    }
};
