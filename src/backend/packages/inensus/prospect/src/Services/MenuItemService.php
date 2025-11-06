<?php

namespace Inensus\Prospect\Services;

class MenuItemService {
    /**
     * Create menu items structure.
     *
     * @return array{menuItem: array<string, string>, subMenuItems: array<int, array<string, string>>}
     */
    public function createMenuItems(): array {
        $menuItem = [
            'name' => '{{Menu-Item}}',
            'url_slug' => '',
            'md_icon' => '',
        ];
        $subMenuItems = [];

        $subMenuItem1 = [
            'name' => '{{Submenu-Item}}',
            'url_slug' => '{{menu-item}}/{{submenu-item}}',
        ];
        $subMenuItems[] = $subMenuItem1;

        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
