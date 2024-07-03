<?php

namespace Inensus\ViberMessaging\Services;


class MenuItemService
{
    const MENU_ITEM = 'Viber Messaging';

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Viber Messaging',
            'url_slug' => '',
            'md_icon' => 'message',
            'usage_type' => 'general',
            'route_data' => json_encode([
                'path' => '/viber-messaging/viber-overview',
                'component' => "plugins/viber-messaging/js/modules/Overview/Overview.vue",
                'meta' => [
                    'layout' => 'default',
                ],
            ])
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/viber-messaging/viber-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];

    }
}