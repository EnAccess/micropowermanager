<?php

namespace App\Http\Controllers;

use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use App\Services\MainSettingsService;
use App\Services\MenuItemsService;
use App\Services\MpmPluginService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CompanyController extends Controller
{

    public function __construct(
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyService $databaseProxyService,
        private PluginsService $pluginsService,
        private MenuItemsService $menuItemsService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $companyData = $request->only(['name', 'address', 'phone', 'email', 'country_id']);
        $company = $this->companyService->create($companyData);

        $adminData = $request->input('user');
        $plugins = $request->input('plugins');

        $companyDatabaseData = [
            'company_id' => $company->getId(),
            'database_name' => str_replace(" ", "",preg_replace('/[^a-z\d_ ]/i', '', $company->getName())) . '_' . Carbon::now()->timestamp,
        ];
        $companyDatabase = $this->companyDatabaseService->create($companyDatabaseData);
        $databaseProxyData = [
            'email' => $adminData['email'],
            'fk_company_id' => $company->getId(),
            'fk_company_database_id' => $companyDatabase->getId()
        ];
        $databaseName = $companyDatabase->database_name;
        $this->databaseProxyService->create($databaseProxyData);
        $this->companyDatabaseService->createNewDatabaseForCompany($databaseName, $company->getId());

        return $this->databaseProxyManagerService->runForCompany($company->getId(),
            function () use ($databaseName, $adminData, $company, $plugins) {
                $this->companyDatabaseService->doMigrations($databaseName);
                $this->companyDatabaseService->runSeeders();
                $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => false]];

                foreach ($plugins as $plugin) {
                    $pluginData = [
                        'mpm_plugin_id' => $plugin['id'],
                        'status' => 1
                    ];
                    $this->pluginsService->create($pluginData);
                    $this->companyDatabaseService->addPluginSpecificMenuItemsToCompanyDatabase($plugin, $company->getId());

                    $mpmPlugin = $this->mpmPluginService->getById($plugin['id']);
                    array_push($registrationTail, [
                        'tag' => $mpmPlugin->tail_tag,
                        'component' => isset($mpmPlugin->tail_tag) ? str_replace(" ", "-",
                            $mpmPlugin->tail_tag) : null,
                        'adjusted' =>
                            false
                    ]);
                    Artisan::call($mpmPlugin->installation_command);

                }

                $this->userService->create([
                    'name' => $adminData['name'],
                    'password' => $adminData['password'],
                    'email' => $adminData['email'],
                    'company_id' => $company->getId(),
                ]);

                $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);
                $mainSettings = $this->mainSettingsService->getAll()->first();
                $this->mainSettingsService->update($mainSettings,['company_name' => $company->name]);

                return response()->json([
                    'message' => 'Congratulations! you have registered to MicroPowerManager successfully. You will be redirected to dashboard  in seconds..',
                    'company' => $company,
                    'sidebarData' => $this->menuItemsService->getMenuItems()
                ], 201);
            });


    }

}
