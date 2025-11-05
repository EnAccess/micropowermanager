<?php

namespace App\Console\Commands\MpmSystemChecks;

use App\Helpers\MailHelper;
use Illuminate\Console\Command;

class EmailCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:email {recipient? : Email address to send test email to}';
    protected $description = 'Check email provider configuration';

    public function __construct(private MailHelper $mailHelper) {
        parent::__construct();
    }

    public function handle(): int {
        $this->info('Checking email configuration...');

        $recipient = $this->argument('recipient');

        if (!$recipient) {
            $recipient = $this->ask('Enter email address to send test email to');
        }

        if (!$recipient) {
            $this->error('No recipient email address provided.');

            return Command::FAILURE;
        }

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');

            return Command::FAILURE;
        }

        try {
            $this->mailHelper->sendPlain(
                $recipient,
                '[TEST] MicroPowerManager Email Configuration Check',
                'This is a test email from MicroPowerManager to verify email configuration is working correctly.'
            );

            $this->info("Test email queued successfully to: {$recipient}");
            $this->info('Check your email inbox and spam folder.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
