<?php

namespace Inensus\ViberMessaging\Services;

class MenuItemService
{
    public const MENU_ITEM = 'Viber Messaging';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Viber Messaging',
            'url_slug' => '',
            'md_icon' => 'message',
            'usage_type' => 'general',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/viber-messaging/viber-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
