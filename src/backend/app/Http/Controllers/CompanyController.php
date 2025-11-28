<?php

namespace App\Http\Controllers;

use App\Helpers\RolesPermissionsPopulator;
use App\Http\Requests\CompanyRegistrationRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\CompanyDatabaseService;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\MpmPluginService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CompanyController extends Controller {
    public function __construct(
        private CompanyService $companyService,
        private CompanyDatabaseService $companyDatabaseService,
        private PluginsService $pluginsService,
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function store(CompanyRegistrationRequest $request): JsonResponse {
        $companyData = $request->only(['name', 'address', 'phone', 'email', 'country_id']);
        $adminData = $request->input('user');
        $plugins = $request->input('plugins');
        $usageType = $request->input('usage_type');

        // Create Company and CompanyDatabase
        $company = $this->companyService->create($companyData);

        $this->companyDatabaseService->create([
            'company_id' => $company->getId(),
            'database_name' => str_replace(' ', '', preg_replace('/[^a-z\d_ ]/i', '', $company->getName())).'_'.
                Carbon::now()->timestamp,
        ]);

        // Populate roles and permissions for the new company
        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function (): void {
                RolesPermissionsPopulator::populate();
            }
        );

        // Create Admin user and DatabaseProxy, then assign owner role within tenant
        $adminUser = $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            fn (): User => $this->userService->create(
                [
                    'name' => $adminData['name'],
                    'password' => $adminData['password'],
                    'email' => $adminData['email'],
                    'company_id' => $company->getId(),
                ],
                $company->getId()
            )
        );

        $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($adminUser): void {
                // Assign owner role to initial company admin
                /* @var \App\Models\User $adminUser */
                if (method_exists($adminUser, 'assignRole')) {
                    $adminUser->assignRole('owner');
                }
            }
        );

        // Set some meaningful settings by default
        $this->databaseProxyManagerService->runForCompany($company->getId(), function () use ($company, $usageType) {
            $mainSettings = $this->mainSettingsService->getAll()->first();
            $this->mainSettingsService->update(
                $mainSettings,
                ['company_name' => $company->name, 'usage_type' => $usageType]
            );
        });

        // Plugin and Registration Tail magic
        return $this->databaseProxyManagerService->runForCompany(
            $company->getId(),
            function () use ($company, $plugins) {
                // Prompt new users to configure their default settings
                $registrationTail = [['tag' => 'Settings', 'component' => 'Settings', 'adjusted' => false]];

                foreach ($plugins as $plugin) {
                    $pluginData = [
                        'mpm_plugin_id' => $plugin['id'],
                        'status' => 1,
                    ];
                    $this->pluginsService->create($pluginData);

                    $mpmPlugin = $this->mpmPluginService->getById($plugin['id']);
                    $registrationTail[] = [
                        'tag' => $mpmPlugin->tail_tag,
                        'component' => isset($mpmPlugin->tail_tag) ? str_replace(
                            ' ',
                            '-',
                            $mpmPlugin->tail_tag
                        ) : null,
                        'adjusted' => !isset($mpmPlugin->tail_tag),
                    ];
                    Artisan::call($mpmPlugin->installation_command);
                }

                $this->registrationTailService->create(['tail' => json_encode($registrationTail)]);

                return response()->json([
                    'message' => 'Congratulations! you have registered to MicroPowerManager successfully. You will be redirected to dashboard  in seconds..',
                    'company' => $company,
                ], 201);
            }
        );
    }

    public function get(string $email): ApiResource {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);

        return ApiResource::make($this->companyService->getByDatabaseProxy($databaseProxy));
    }
}
