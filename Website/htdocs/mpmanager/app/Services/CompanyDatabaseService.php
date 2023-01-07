<?php

namespace App\Services;

use App\Models\CompanyDatabase;
use Exception;
use Illuminate\Support\Facades\Artisan;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CompanyDatabaseService implements IBaseService
{
    public function __construct(private CompanyDatabase $companyDatabase)
    {
    }

    public function getById($id): CompanyDatabase
    {
        /** @var CompanyDatabase $result */
        $result =  $this->companyDatabase->newQuery()->find($id);

        return $result;
    }

    public function create($data): CompanyDatabase
    {
        /** @var CompanyDatabase $result */
        $result =  $this->companyDatabase->newQuery()->create($data);

        return $result;
    }

    public function update($model, $data): void
    {
        // TODO: Implement update() method.
    }

    public function delete($model): void
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null): void
    {
        // TODO: Implement getAll() method.
    }

    public function createNewDatabaseForCompany(string $databaseName, int $companyId): void
    {
        $sourcePath = __DIR__ . '/../../';
        shell_exec(__DIR__ . '/../../database_creator.sh --database='
            . $databaseName . ' --user=root' . ' --path=' . $sourcePath . ' --company_id=' . $companyId);
    }

    public function doMigrations(): void
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--database' => 'shard',
            '--path' => '/database/migrations/micropowermanager',
        ]);
    }

    public function runSeeders(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
    }

    public function addPluginSpecificMenuItemsToCompanyDatabase($plugin, ?int $companyId = null): void
    {
        $rootClass = $plugin['root_class'];
        try {
            $menuItemService = app()->make(sprintf('Inensus\%s\Services\MenuItemService', $rootClass));
        } catch (Exception $exception) {
            // we return here if company chooses a plugin which does not have UI components
            return;
        }
        $menuItems = $menuItemService->createMenuItems();

        if ($companyId !== null) {
            /** @var DatabaseProxyManagerService $databaseProxyManagerService */
            $databaseProxyManagerService = app()->make(DatabaseProxyManagerService::class);
            $databaseProxyManagerService->runForCompany($companyId, function () use ($menuItems) {
                Artisan::call('menu-items:generate', [
                    'menuItem' => $menuItems['menuItem'],
                    'subMenuItems' => $menuItems['subMenuItems'],
                ]);
            });
        } else {
            Artisan::call('menu-items:generate', [
                'menuItem' => $menuItems['menuItem'],
                'subMenuItems' => $menuItems['subMenuItems'],
            ]);
        }
    }

    public function findByCompanyId(int $companyId): CompanyDatabase
    {
        /** @var CompanyDatabase $result */
        $result = $this->companyDatabase->newQuery()
            ->where(CompanyDatabase::COL_COMPANY_ID, '=', $companyId)
            ->firstOrFail();

        return $result;
    }
}
