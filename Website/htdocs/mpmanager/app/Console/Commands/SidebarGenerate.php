<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Illuminate\Console\Command;

class SidebarGenerate extends Command
{
    private $menuItemsService;

    protected $signature = 'sidebar:generate';

    protected $description = 'Generating Sidebar Menu Items';

    public function __construct(MenuItemsService $menuItemsService)
    {
        parent::__construct();
        $this->menuItemsService = $menuItemsService;
    }

    public function handle(): void
    {
        $path = 'resources/assets/js/components/Sidebar/menu.json';
        $data = $this->menuItemsService->getMenuItems();
        file_put_contents($path, $data);
    }
}
