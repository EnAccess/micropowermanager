<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Models\CompanyDatabase;
use App\Plugins\WhatsApp\Services\WhatsAppDeliveryService;
use App\Plugins\WhatsApp\Services\WhatsAppTenantConnectionService;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;

class ReconcileDelivery extends Command {
    protected $signature = 'whatsapp:reconcile-delivery {--company= : Limit processing to an existing company ID}';
    protected $description = 'Reconcile durable WhatsApp status events without resending messages';

    public function handle(WhatsAppDeliveryService $delivery, WhatsAppTenantConnectionService $connections): int {
        if (!config('whatsapp.webhooks_enabled') || !app()->environment('local', 'development', 'testing')) {
            $this->error('WhatsApp reconciliation is disabled in this environment.');

            return self::FAILURE;
        }
        $company = $this->option('company');
        if ($company !== null && (!ctype_digit($company) || (int) $company < 1)) {
            $this->error('Invalid company ID.');

            return self::INVALID;
        }
        $companies = CompanyDatabase::query()->whereHas('company');
        if ($company !== null) {
            $companies->where('company_id', (int) $company);
            if (!$companies->exists()) {
                $this->error('Invalid company ID.');

                return self::INVALID;
            }
        }
        $result = self::SUCCESS;
        foreach ($companies->cursor() as $database) {
            $companyId = $database->company_id;
            try {
                $installed = $connections->run($companyId, fn (Connection $connection): bool => $connection->getSchemaBuilder()->hasTable('whatsapp_delivery_events'));
                if (!$installed) {
                    continue;
                }
                $counts = $delivery->reconcile($companyId);
                $this->line('Company '.$companyId.': '.json_encode($counts, JSON_THROW_ON_ERROR));
                if ($counts['processing_errors'] || $counts['exhausted'] > 0 || $counts['unsupported'] > 0) {
                    $result = self::FAILURE;
                }
            } catch (\Throwable) {
                Log::error('WhatsApp delivery reconciliation failed.', ['companyId' => $companyId]);
                $result = self::FAILURE;
            }
        }

        return $result;
    }
}
