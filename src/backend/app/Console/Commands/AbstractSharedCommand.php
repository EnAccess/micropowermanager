<?php

namespace App\Console\Commands;

use App\Models\CompanyDatabase;
use App\Services\CompanyService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Input\InputInterface;
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
            \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
            'The ID of the company to run the command for. If not provided, runs for all companies.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $companyService = app()->make(CompanyService::class);

        $companyId = null;
        if ($this->hasOption('company-id')) {
            $companyId = $this->option('company-id');
        }
        if ($companyId) {
            $this->runForCompany($companyService, (int) $companyId, $input, $output);
        } else {
            $this->runForAllTenants($companyService, $input, $output);
        }

        return $this->EXECUTION_TYPE;
    }

    private function runForAllTenants(
        CompanyService $companyService,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $companyService->queryAllConnections()
            ->chunkById(50, function (Collection $modelCollection) use ($companyService, $input, $output) {
                $modelCollection->map(function (CompanyDatabase $companyDatabase) use (
                    $companyService,
                    $input,
                    $output
                ) {
                    $this->runForCompany(
                        $companyService,
                        $companyDatabase->getCompanyId(),
                        $input,
                        $output
                    );
                });
            });
    }

    private function runForCompany(
        CompanyService $companyService,
        int $companyId,
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->info('Running '.$this->name.' for company ID : '.$companyId);
        $companyService->runForCompany($companyId, function () use ($input, $output) {
            parent::execute($input, $output);
        });
    }
}
