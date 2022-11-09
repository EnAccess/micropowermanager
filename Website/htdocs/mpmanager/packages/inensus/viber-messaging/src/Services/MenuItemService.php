<?php

namespace Inensus\ViberMessaging\Services;


class MenuItemService
{
    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Viber Messaging',
            'url_slug' => '',
            'md_icon' => 'message'
        ];
        $subMenuItems= array();

        $subMenuItem1=[
            'name' => 'Overview',
            'url_slug' => '/viber-messaging/viber-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem'=>$menuItem,'subMenuItems'=>$subMenuItems];


    }
}