<?php

namespace App\Plugins\WhatsApp\Services;

use App\Models\CompanyDatabase;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppWebhookCredentialReader {
    public function __construct(private CompanyDatabase $companyDatabase, private ConnectionFactory $connectionFactory) {}

    /** @return array{secret: string, account: string, phone: string} */
    public function read(int $companyId, bool $verification): array {
        $connection = null;
        try {
            $database = $this->companyDatabase->newQuery()->where('company_id', $companyId)->whereHas('company')->first();
            if ($database === null) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            $configuration = config('database.connections.micro_power_manager');
            unset($configuration['url'], $configuration['read'], $configuration['write']);
            $configuration['database'] = $database->database_name;
            // A factory connection is not registered as the application's active tenant.
            $connection = $this->connectionFactory->make($configuration);
            $field = $verification ? 'webhook_verify_token' : 'meta_app_secret';
            $credential = $connection->table('whatsapp_credentials')->select([
                'is_enabled', 'whatsapp_business_account_id', 'phone_number_id', $field,
            ])->first();
            if ($credential === null || !$credential->is_enabled || empty($credential->whatsapp_business_account_id)
                || empty($credential->phone_number_id) || empty($credential->{$field})) {
                throw new HttpException(403, 'Webhook request rejected.');
            }
            $secret = Crypt::decryptString($credential->{$field});
            if (!WhatsAppCredentialService::isValidSecret($secret) || $secret === '') {
                throw new HttpException(403, 'Webhook request rejected.');
            }

            return ['secret' => $secret, 'account' => $credential->whatsapp_business_account_id, 'phone' => $credential->phone_number_id];
        } catch (\Throwable) {
            // Never attach database errors, ciphertext or a previous exception to the response.
            throw new HttpException(403, 'Webhook request rejected.');
        } finally {
            $connection?->disconnect();
        }
    }
}
