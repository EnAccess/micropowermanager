<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\CompanyDatabaseService;
use App\Services\MenuItemsService;
use App\Services\MpmPluginService;
use App\Services\PluginsService;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class PluginController extends Controller
{

    public function __construct(
        private PluginsService $pluginsService,
        private MpmPluginService $mpmPluginService,
        private CompanyDatabaseService $companyDatabaseService,
        private MenuItemsService $menuItemsService,
        private RegistrationTailService $registrationTailService
    ) {
    }

    public function index(Request $request): ApiResource
    {
        return ApiResource::make($this->pluginsService->getAll());
    }

    public function update(Request $request, $mpmPluginId): ApiResource
    {
        $plugin = $this->pluginsService->getByMpmPluginId($mpmPluginId);
        $mpmPlugin = $this->mpmPluginService->getById($mpmPluginId);
        $registrationTail = $this->registrationTailService->getFirst();
        $tail = json_decode($registrationTail->tail, true);
        $pluginData = [
            'mpm_plugin_id' => $mpmPluginId,
            'status' => $request->input('checked')
        ];

        if (!$plugin && !$request->input('checked'))
        {
            throw new \Exception(['message' => 'Plugin not found']);
        }

        if ($request->input('checked'))
        {
            if(!$plugin)
            {
                $createdPlugin = $this->pluginsService->create($pluginData);
                $this->companyDatabaseService->addPluginSpecificMenuItemsToCompanyDatabase($mpmPlugin);
                $this->registrationTailService->resetTail($tail, $mpmPlugin, $registrationTail);
                Artisan::call($mpmPlugin->installation_command);

                return ApiResource::make($createdPlugin);
            }

            $updatedPlugin = $this->pluginsService->update($plugin, $pluginData);
            $ExistingMenuItem = $this->menuItemsService->checkMenuItemIsExistsForTag($mpmPlugin->tail_tag);

            if (!$ExistingMenuItem) {
                $this->companyDatabaseService->addPluginSpecificMenuItemsToCompanyDatabase($mpmPlugin);
            }

            $this->registrationTailService->resetTail($tail, $mpmPlugin, $registrationTail);
        }
        else
        {
            $updatedPlugin = $this->pluginsService->update($plugin, $pluginData);

            //since we do not force the user to configure bulk registrations.
            if ($mpmPlugin->name === 'BulkRegistration')
            {
                $this->menuItemsService->removeMenuItemAndSubmenuItemForMenuItemName('Bulk Registration');
            }else{
                $this->menuItemsService->removeMenuItemAndSubmenuItemForMenuItemName($mpmPlugin->tail_tag);
            }


            $updatedTail = array_filter($tail, function ($item) use ($mpmPlugin) {
                return $item['tag'] !== $mpmPlugin->tail_tag;
            }) ;

            $this->registrationTailService->update(
                $registrationTail,['tail' => array_values($updatedTail)] );
        }
        return ApiResource::make($updatedPlugin);

    }

}
