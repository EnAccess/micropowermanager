<?php

namespace Inensus\{{Package-Name}}\Services;


class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' =>'{{Menu-Item}}',
            'url_slug' =>'',
            'md_icon' =>''
        ];
        $subMenuItems= array();

        $subMenuItem1=[
            'name' =>'{{Submenu-Item}}',
            'url_slug' =>'{{menu-item}}/{{submenu-item}}',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}