<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRegistrationRequest;
use App\Http\Resources\ApiResource;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\MenuItemsService;
use App\Services\MpmPluginService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private PluginsService $pluginsService,
        private MenuItemsService $menuItemsService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService
    ) {
    }

    public function store(CompanyRegistrationRequest $request): JsonResponse
    {
        $companyData = $request->only(['name', 'address', 'phone', 'email', 'country_id','protected_page_password']);
        $company = $this->companyService->create($companyData);

        $adminData = $request->input('user');
        $plugins = $request->input('plugins');
        $usageType = $request->input('usage_type');

        $companyDatabaseData = [
            'company_id' => $company->getId(),
            'database_name' => str_replace(" ", "", preg_replace('/[^a-z\d_ ]/i', '', $company->getName())) . '_' .
                Carbon::now()->timestamp,
        ];
        $companyDatabase = $this->companyDatabaseService->create($companyDatabaseData);
        $databaseName = $companyDatabase->database_name;
        $this->companyDatabaseService->createNewDatabaseForCompany($databaseName, $company->getId());

        return $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($adminData, $company, $plugins, $usageType) {
                $this->companyDatabaseService->doMigrations();
                $this->companyDatabaseService->runSeeders();
                $this->userService->create([
                    'name' => $adminData['name'],
                    'password' => $adminData['password'],
                    'email' => $adminData['email'],
                    'company_id' => $company->getId(),
                ], $company->getId());
                $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => false]];

                foreach ($plugins as $plugin) {
                    $pluginData = [
                        'mpm_plugin_id' => $plugin['id'],
                        'status' => 1
                    ];
                    $this->pluginsService->create($pluginData);
                    $this->companyDatabaseService->addPluginSpecificMenuItemsToCompanyDatabase(
                        $plugin,
                        $company->getId()
                    );

                    $mpmPlugin = $this->mpmPluginService->getById($plugin['id']);
                    $registrationTail[] = [
                        'tag' => $mpmPlugin->tail_tag,
                        'component' => isset($mpmPlugin->tail_tag) ? str_replace(
                            " ",
                            "-",
                            $mpmPlugin->tail_tag
                        ) : null,
                        'adjusted' => !isset($mpmPlugin->tail_tag)

                    ];
                    Artisan::call($mpmPlugin->installation_command);
                }

                $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);
                $mainSettings = $this->mainSettingsService->getAll()->first();
                $this->mainSettingsService->update($mainSettings, ['company_name' => $company->name, 'usage_type' => $usageType]);

                return response()->json([
                    'message' => 'Congratulations! you have registered to MicroPowerManager successfully. You will be redirected to dashboard  in seconds..',
                    'company' => $company,
                    'sidebarData' => $this->menuItemsService->getAll()
                ], 201);
            }
        );
    }

    public function get($email): ApiResource
    {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);
        return ApiResource::make($this->companyService->getByDatabaseProxy($databaseProxy));
    }
}
