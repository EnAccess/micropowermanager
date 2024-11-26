<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use MPM\DatabaseProxy\DatabaseProxyManagerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractSharedCommand extends Command
{
    protected const EXECUTE_FOR_ONE = 1;
    protected const EXECUTE_FOR_ALL = 0;

    protected int $EXECUTION_TYPE = self::EXECUTE_FOR_ALL;

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->addOption(
            'company-id',
            null,
            \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
            'The ID of the company to run the command for. If not provided, runs for all companies.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DatabaseProxyManagerService $databaseProxyManagerService */
        $databaseProxyManagerService = app()->make(DatabaseProxyManagerService::class);

        $companyId = null;
        if ($this->hasOption('company-id')) {
            $companyId = $this->option('company-id');
        }
        if ($companyId) {
            $this->runForCompany($databaseProxyManagerService, (int) $companyId, $input, $output);
        } else {
            $this->runForAllShards($databaseProxyManagerService, $input, $output);
        }

        return $this->EXECUTION_TYPE;
    }

    private function runForAllShards(
        DatabaseProxyManagerService $databaseProxyManagerService,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $databaseProxyManagerService->queryAllConnections()
            ->chunkById(50, function (Collection $modelCollection) use ($databaseProxyManagerService, $input, $output) {
                $modelCollection->map(function (CompanyDatabase $companyDatabase) use (
                    $databaseProxyManagerService,
                    $input,
                    $output
                ) {
                    $this->runForCompany(
                        $databaseProxyManagerService,
                        $companyDatabase->getCompanyId(),
                        $input,
                        $output
                    );
                });
            });
    }

    private function runForCompany(
        DatabaseProxyManagerService $databaseProxyManagerService,
        int $companyId,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->info('Running '.$this->name.' for company ID : '.$companyId);
        $databaseProxyManagerService->runForCompany($companyId, function () use ($input, $output) {
            parent::execute($input, $output);
        });
    }
}
