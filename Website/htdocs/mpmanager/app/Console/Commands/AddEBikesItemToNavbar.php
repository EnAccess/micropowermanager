<?php

namespace App\Console\Commands;

use App\Services\MenuItemsService;
use Illuminate\Console\Command;

class AddEBikesItemToNavbar extends AbstractSharedCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'navbar:add-ebikes-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add ebikes item to navbar';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(private MenuItemsService $menuItemsService,)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->addSolarHomeSystemsToNavBar();
        return 0;
    }

    private function addSolarHomeSystemsToNavBar()
    {
        $eBikesMenuItem = 'E-Bikes';
        $menuItem = $this->menuItemsService->getByName($eBikesMenuItem);

        if (!$menuItem) {
            $this->menuItemsService->create([
                'name' => $eBikesMenuItem,
                'url_slug' => '/e-bikes/page/1',
                'md_icon' => 'electric_bike',
                'menu_order' => 0,
            ]);
        }
    }
}
