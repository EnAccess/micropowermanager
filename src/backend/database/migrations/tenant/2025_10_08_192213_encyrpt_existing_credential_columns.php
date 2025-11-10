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
        // Define credential tables and their fields that need encryption
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
        ];

        foreach ($credentialTables as $tableName => $fields) {
            if (Schema::connection('tenant')->hasTable($tableName)) {
                // Change column type to text for all credential fields
                Schema::connection('tenant')->table($tableName, function (Blueprint $table) use ($fields) {
                    foreach ($fields as $field) {
                        $table->text($field)->nullable()->change();
                    }
                });

                // Encrypt existing data
                $this->encryptExistingCredentials($tableName, $fields);
            }
        }
    }

    /**
     * Encrypt existing credentials in the database.
     *
     * @param string        $tableName
     * @param array<string> $fields
     *
     * @return void
     */
    private function encryptExistingCredentials(string $tableName, array $fields): void {
        $records = DB::connection('tenant')->table($tableName)->get();

        foreach ($records as $record) {
            $updateData = [];

            foreach ($fields as $field) {
                $value = $record->$field;

                // Only encrypt if the value is not null and not already encrypted
                if ($value !== null && !$this->isEncrypted($value)) {
                    try {
                        $updateData[$field] = Crypt::encryptString($value);
                    } catch (Exception $e) {
                        // Skip encryption if there's an error
                        continue;
                    }
                }
            }

            if (!empty($updateData)) {
                DB::connection('tenant')->table($tableName)
                    ->where('id', $record->id)
                    ->update($updateData);
            }
        }
    }

    /**
     * Check if a value is already encrypted.
     */
    private function isEncrypted(string $value): bool {
        // Laravel encrypted strings typically start with "eyJpdiI6"
        return str_starts_with($value, 'eyJpdiI6') || str_starts_with($value, 'eyJ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        // Define credential tables and their fields that need decryption
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
                // Decrypt existing data
                $this->decryptExistingCredentials($tableName, $fields);
            }
        }
    }

    /**
     * Decrypt existing credentials in the database.
     *
     * @param string        $tableName
     * @param array<string> $fields
     *
     * @return void
     */
    private function decryptExistingCredentials(string $tableName, array $fields): void {
        $records = DB::connection('tenant')->table($tableName)->get();

        foreach ($records as $record) {
            $updateData = [];

            foreach ($fields as $field) {
                $value = $record->$field;

                // Only decrypt if the value is not null and appears to be encrypted
                if ($value !== null && $this->isEncrypted($value)) {
                    try {
                        $updateData[$field] = Crypt::decryptString($value);
                    } catch (Exception $e) {
                        // Skip decryption if there's an error
                        continue;
                    }
                }
            }

            if (!empty($updateData)) {
                DB::connection('tenant')->table($tableName)
                    ->where('id', $record->id)
                    ->update($updateData);
            }
        }
    }
};
