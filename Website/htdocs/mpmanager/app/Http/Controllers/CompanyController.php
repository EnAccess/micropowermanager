<?php

namespace App\Http\Controllers;

use App\Http\Middleware\UserDefaultDatabaseConnectionMiddleware;
use App\Models\Company;
use App\Models\CompanyDatabase;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Services\MenuItemsService;
use App\Services\PluginsService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CompanyController extends Controller
{

    public function __construct(
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyService $databaseProxyService,
        private PluginsService $pluginsService,
        private MenuItemsService $menuItemsService,
        private UserService $userService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $companyData =$request->only(['name', 'address', 'phone', 'email']);
        $company = $this->companyService->create($companyData);
        $adminData = $request->input('user');
        $plugins = $request->input('plugins');
        $companyDatabaseData = [
            'company_id' => $company->id,
            'database_name' => str_replace(" ", "", $company->name) . '_' . Carbon::now()->timestamp,
        ];
        $companyDatabase = $this->companyDatabaseService->create($companyDatabaseData);
        $databaseProxyData = [
            'email' => $adminData['email'],
            'fk_company_id' => $company->id,
            'fk_company_database_id' => $companyDatabase->id
        ];
        $databaseName = $companyDatabase->database_name;
        $this->databaseProxyService->create($databaseProxyData);
        $this->companyDatabaseService->createNewDatabaseForCompany($databaseName);
        $this->companyDatabaseService->setDatabaseConnectionForCompany($databaseName);
        $this->companyDatabaseService->doMigrations($databaseName);
        $this->companyDatabaseService->runSeeders();
        foreach ($plugins as $plugin) {
            $pluginData = [
                'mpm_plugin_id' => $plugin['id'],
                'status' => 1
            ];
            $this->pluginsService->create($pluginData);
            $this->companyDatabaseService->addPluginSpecificMenuItemsToCompanyDatabase($plugin);
        }

        $this->userService->create([
            'name'=>$adminData['name'],
            'password'=>$adminData['password'],
            'email'=>$adminData['email'],
            'company_id'=>$company->id
        ]);

        return response()->json([
            'message' => 'Congratulations! you have registered to MicroPowerManager successfully. You will be redirected to login page in seconds..',
            'company' => $company,
            'sidebarData' =>$this->menuItemsService->getMenuItems()
        ], 201);
    }

}
