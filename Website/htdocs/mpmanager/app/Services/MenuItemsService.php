<?php

namespace App\Services;

use App\Models\MenuItems;
use App\Models\SubMenuItems;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MenuItemsService
{
    private $menuItems;
    private $subMenuItems;
    public function __construct(MenuItems $menuItems, SubMenuItems $subMenuItems)
    {
        $this->menuItems = $menuItems;
        $this->subMenuItems = $subMenuItems;
    }

    /**
     * @return Builder[]|Collection
     *
     * @psalm-return \Illuminate\Database\Eloquent\Collection|array<array-key, \Illuminate\Database\Eloquent\Builder>
     */
    public function getMenuItems()
    {
        return $this->menuItems->newQuery()->with('SubMenuItems')->orderBy('menu_order')->get();
    }

    public function createMenuItems($menuItem, $subMenuItems): void
    {
        $lastOrder = $this->menuItems->newQuery()->latest()->first();
        $menuItem = $this->menuItems->newQuery()->firstOrCreate(['name' => $menuItem['name']], [
            'name' => $menuItem['name'],
            'url_slug' => $menuItem['url_slug'],
            'md_icon' => $menuItem['md_icon'],
            'menu_order' => $lastOrder ? $lastOrder->menu_order + 1 : 1,
        ]);

        foreach ($subMenuItems as $key => $value) {
            $this->subMenuItems->newQuery()->firstOrCreate(
                ['url_slug' => $value['url_slug']],
                [
                'name' => $value['name'],
                'url_slug' => $value['url_slug'],
                'parent_id' => $menuItem->id
                ]
            );
        }
    }
}
