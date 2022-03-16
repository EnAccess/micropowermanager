<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Illuminate\Console\Command;

class MenuItemsGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu-items:generate {menuItem} {subMenuItems}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates new menu items and related submenu items';


    private $menuItemService;

    public function __construct(MenuItemsService $menuItemService)
    {
         parent::__construct();
         $this->menuItemService = $menuItemService;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $menuItem = $this->argument('menuItem');
        $subMenuItems = $this->argument('subMenuItems');
        $this->menuItemService->createMenuItems($menuItem, $subMenuItems);
        $this->info('Menu item records has been created.');
    }
}
