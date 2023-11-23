<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Illuminate\Console\Command;

class MenuItemsGenerator extends Command
{
    protected $signature = 'menu-items:generate {menuItem} {subMenuItems}';
    protected $description = 'Creates new menu items and related submenu items';


    public function __construct(private MenuItemsService $menuItemService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $menuItem = $this->argument('menuItem');
        $subMenuItems = $this->argument('subMenuItems');
        $this->menuItemService->createMenuItems($menuItem, $subMenuItems);
        $this->info('Menu item records has been created.');
    }
}
