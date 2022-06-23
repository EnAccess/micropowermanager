<?php

namespace App\Services;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use App\Models\CompanyDatabase;
use Illuminate\Support\Facades\Artisan;

class CompanyDatabaseService implements IBaseService
{
    public function __construct(private CompanyDatabase $companyDatabase)
    {
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($companyDatabaseData)
    {
        return $this->companyDatabase->newQuery()->create($companyDatabaseData);
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }

    public function createNewDatabaseForCompany($databaseName)
    {
        $sourcePath = __DIR__ . '/../../';
        shell_exec(__DIR__ . '/../../database_creator.sh --database='
            . $databaseName . ' --user=root' . ' --path='
            . $sourcePath);
    }

    public function setDatabaseConnectionForCompany($databaseName)
    {
        $middleware = app()->make(UserDefaultDatabaseConnectionMiddleware::class);
        $middleware->buildDatabaseConnection($databaseName);
    }

    public function doMigrations($databaseName)
    {
        Artisan::call('migrate', [
            '--database' => 'shard',
            '--path' => '/database/migrations/' . $databaseName,
        ]);
    }

    public function runSeeders()
    {
        Artisan::call('db:seed', ['--force' => true]);
    }

    public function addPluginSpecificMenuItemsToCompanyDatabase($plugin)
    {
        $pluginName = $plugin['name'];
        try {
            $menuItemService = app()->make(sprintf('Inensus\%s\Services\MenuItemService', $pluginName));

        } catch (\Exception $exception) {
            // we return here if company chooses a plugin which does not have UI components
            return 0;
        }
        $menuItems = $menuItemService->createMenuItems();
        Artisan::call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);

    }
}