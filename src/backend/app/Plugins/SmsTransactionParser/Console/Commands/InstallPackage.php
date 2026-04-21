<?php

namespace App\Plugins\SmsTransactionParser\Console\Commands;

use App\Plugins\SmsTransactionParser\Services\SmsParsingRuleService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'sms-transaction-parser:install';
    protected $description = 'Install SmsTransactionParser Package';

    public function __construct(
        private SmsParsingRuleService $smsParsingRuleService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SmsTransactionParser Integration Package\n');

        $this->smsParsingRuleService->installDefaults();

        $this->info('Package installed successfully..');
    }
}
