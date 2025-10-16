<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use MPM\DatabaseProxy\DatabaseProxyManagerService;
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

        if ($companyId) {
            $this->runForCompany($databaseProxyManagerService, (int) $companyId, $input, $output);
        } else {
            $this->runForAllTenants($databaseProxyManagerService, $input, $output);
        }

        return $this->EXECUTION_TYPE;
    }

    private function runForAllTenants(
        DatabaseProxyManagerService $databaseProxyManagerService,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $databaseProxyManagerService->queryAllConnections()
            ->chunkById(50, function (Collection $modelCollection) use ($databaseProxyManagerService, $input, $output) {
                /* @var \Illuminate\Support\Collection<int, \App\Models\CompanyDatabase> $modelCollection */
                $modelCollection->each(function ($companyDatabase) use (
                    $databaseProxyManagerService,
                    $input,
                    $output
                ) {
                    // @phpstan-ignore instanceof.alwaysTrue
                    if ($companyDatabase instanceof CompanyDatabase) {
                        $this->runForCompany(
                            $databaseProxyManagerService,
                            $companyDatabase->getCompanyId(),
                            $input,
                            $output
                        );
                    }
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

    /**
     * Get the company database.
     */
    protected function getCompanyDatabase(?string $companyId = null): CompanyDatabase {
        try {
            if ($companyId === null) {
                $companyId = $this->option('company-id');
            }

            if ($companyId) {
                return app(CompanyDatabase::class)->findByCompanyId((int) $companyId);
            }

            return app(CompanyDatabase::class)->newQuery()->first();
        } catch (\Exception $e) {
            throw new \Exception('Unable to find company database: '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
