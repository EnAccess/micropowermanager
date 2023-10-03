<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Log;
use MPM\CustomBulkRegistration\Ecosys\CustomerDatabaseParser;

class EcosysCustomerDatabaseParser extends AbstractSharedCommand
{
    protected $signature = 'ecosys:db-file-parser {--company-id=}';
    protected $description = 'custom database file parser for ecosys';

    public function __construct(private CustomerDatabaseParser $customerDatabaseParser)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Command started');
        $result =  $this->customerDatabaseParser->insertFromCsv();
        foreach ($result as $key => $value) {
            $this->info($key . ' : ' . $value);
        }
        $this->info('Command finished');
    }
}