<?php

namespace Inensus\SparkMeter\Services;



class MenuItemService
{

    public function createMenuItems()
    {
        $menuItem = [
            'name' => 'Spark Meter',
            'url_slug' => '',
            'md_icon' => 'bolt'
        ];
        $subMenuItems = array();

        $subMenuItem1 = [
            'name' => 'Overview',
            'url_slug' => '/spark-meters/sm-overview',
        ];
        array_push($subMenuItems, $subMenuItem1);

        $subMenuItem2 = [
            'name' => 'Sites',
            'url_slug' => '/spark-meters/sm-site/page/1',
        ];
        array_push($subMenuItems, $subMenuItem2);

        $subMenuItem3 = [
            'name' => 'Meter Models',
            'url_slug' => '/spark-meters/sm-meter-model/page/1',
        ];
        array_push($subMenuItems, $subMenuItem3);

        $subMenuItem4 = [
            'name' => 'Tariffs',
            'url_slug' => '/spark-meters/sm-tariff/page/1',
        ];
        array_push($subMenuItems, $subMenuItem4);

        $subMenuItem5 = [
            'name' => 'Customers',
            'url_slug' => '/spark-meters/sm-customer/page/1',
        ];
        array_push($subMenuItems, $subMenuItem5);
        $subMenuItem6 = [
            'name' => 'Sales Accounts',
            'url_slug' => '/spark-meters/sm-sales-account/page/1',
        ];
        array_push($subMenuItems, $subMenuItem6);
        $subMenuItem7 = [
            'name' => 'Settings',
            'url_slug' => '/spark-meters/sm-setting',
        ];
        array_push($subMenuItems, $subMenuItem7);
        return ['menuItem' => $menuItem, 'subMenuItems' => $subMenuItems];
    }
}
