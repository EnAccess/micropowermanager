<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Illuminate\Console\Command;

class SidebarGenerate extends AbstractSharedCommand
{
    protected $signature = 'sidebar:generate';
    protected $description = 'Generating Sidebar Menu Items';

    public function __construct(private MenuItemsService $menuItemsService)
    {
        parent::__construct();
    }

    public function runInCompanyScope(): void
    {
        $path = 'resources/assets/js/components/Sidebar/menu.json';
        $data = $this->menuItemsService->getMenuItems();
        file_put_contents($path, $data);
    }
}
