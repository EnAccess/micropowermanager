<?php

namespace App\Console\Commands;

use App\Services\DatabaseProxyManagerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSharedCommand extends Command {
    protected const EXECUTE_FOR_ONE = 1;
    protected const EXECUTE_FOR_ALL = 0;

    protected int $EXECUTION_TYPE = self::EXECUTE_FOR_ALL;

    /**
     * Configure the command.
     */
    protected function configure(): void {
        parent::configure();

        $this->addOption(
            'company-id',
            null,
            InputOption::VALUE_OPTIONAL,
            'The ID of the company to run the command for. If not provided, runs for all companies.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $databaseProxyManagerService = app()->make(DatabaseProxyManagerService::class);

        $companyId = $this->option('company-id');

        try {
            if ($companyId) {
                $this->runForCompany($databaseProxyManagerService, (int) $companyId, $input, $output);
            } else {
                $this->runForAllTenants($databaseProxyManagerService, $input, $output);
            }
        } catch (\Throwable $e) {
            Log::error('Command ['.$this->name.'] failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Command failed: '.$e->getMessage());

            throw $e;
        }

        return $this->EXECUTION_TYPE;
    }

    private function runForAllTenants(
        DatabaseProxyManagerService $databaseProxyManagerService,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $databaseProxyManagerService->eachCompany(
            function (int $companyId) use ($input, $output): void {
                $this->info('Running '.$this->name.' for company ID : '.$companyId);
                parent::execute($input, $output);
            },
            function (int $companyId, \Throwable $throwable): void {
                $this->logFailure($companyId, $throwable);

                throw $throwable;
            }
        );
    }

    private function runForCompany(
        DatabaseProxyManagerService $databaseProxyManagerService,
        int $companyId,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->info('Running '.$this->name.' for company ID : '.$companyId);

        try {
            $databaseProxyManagerService->runForCompany($companyId, function () use ($input, $output) {
                parent::execute($input, $output);
            });
        } catch (\Throwable $e) {
            $this->logFailure($companyId, $e);

            throw $e;
        }
    }

    private function logFailure(int $companyId, \Throwable $throwable): void {
        Log::error('Command ['.$this->name.'] failed for company ID: '.$companyId, [
            'company_id' => $companyId,
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
        ]);
    }
}
