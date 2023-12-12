<?php

namespace App\Services;

use App\Models\MenuItems;
use App\Models\SubMenuItems;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MenuItemsService implements IBaseService
{
    public function __construct(private MenuItems $menuItems, private SubMenuItems $subMenuItems)
    {
    }


    public function getAll($limit = null): LengthAwarePaginator|Collection
    {
        if ($limit) {
            return $this->menuItems->newQuery()->with('SubMenuItems')->orderBy('menu_order')->paginate($limit);
        }
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

    public function checkMenuItemIsExistsForTag($plugin)
    {
        $rootClass = $plugin['root_class'];
        try {
            $menuItemService = app()->make(sprintf('Inensus\%s\Services\MenuItemService', $rootClass));
        } catch (\Exception $exception) {
            // we return here if company chooses a plugin which does not have UI components
            return 0;
        }
        $menuItem = $menuItemService::MENU_ITEM;

        return $this->menuItems->newQuery()->where('name', $menuItem)->first();
    }

    public function removeMenuItemAndSubmenuItemForMenuItemName($plugin)
    {

        $rootClass = $plugin['root_class'];
        try {
            $menuItemService = app()->make(sprintf('Inensus\%s\Services\MenuItemService', $rootClass));
        } catch (\Exception $exception) {
            // we return here if company chooses a plugin which does not have UI components
            return 0;
        }
        $menuItemName = $menuItemService::MENU_ITEM;
        $menuItem = $this->menuItems->newQuery()->where('name', $menuItemName)->first();
        if ($menuItem) {
            $this->subMenuItems->newQuery()->where('parent_id', $menuItem->id)->each(function ($subMenuItem) {
                $subMenuItem->delete();
            });
            $menuItem->delete();
        }
    }

    public function getByName($name)
    {
        return $this->menuItems->newQuery()->where('name', $name)->first();
    }

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function create($data)
    {
        $lastOrder = $this->menuItems->newQuery()->latest()->first();
        $data['menu_order'] = $lastOrder ? $lastOrder->menu_order + 1 : 1;

        return $this->menuItems->newQuery()->create($data);
    }

    public function update($model, $data)
    {
        throw new \Exception("not implemented");
    }

    public function delete($model)
    {
        throw new \Exception("not implemented");
    }
}
