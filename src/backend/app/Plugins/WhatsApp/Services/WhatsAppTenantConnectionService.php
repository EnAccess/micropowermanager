<?php

namespace App\Plugins\WhatsApp\Services;

use App\Models\CompanyDatabase;
use Illuminate\Database\Connectors\ConnectionFactory;

/**
 * Callback authentication and reconciliation must select the same trusted tenant without
 * inheriting or changing a long-lived process's active database connection.
 */
class WhatsAppTenantConnectionService {
    public function __construct(private CompanyDatabase $companyDatabase, private ConnectionFactory $connectionFactory) {}

    public function run(int $companyId, \Closure $operation): mixed {
        $database = $this->companyDatabase->newQuery()->where('company_id', $companyId)->whereHas('company')->firstOrFail();
        $configuration = config('database.connections.micro_power_manager');
        unset($configuration['url'], $configuration['read'], $configuration['write']);
        $configuration['database'] = $database->database_name;
        $connection = $this->connectionFactory->make($configuration);
        try {
            return $operation($connection);
        } finally {
            $connection->disconnect();
        }
    }
}
