<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 10:08
 */


return [

    'table_names' => [
        'board' => 'ticket_boards',
        'board_categories' => 'ticket_board_categories',
        'card' => 'ticket_cards',
        'ticket' => 'tickets',
        'user' => 'ticket_users',
        'ticket_categories' => 'ticket_categories',
        'ticket_outsource' => 'ticket_outsources',
        'outsource_reports' => 'ticket_outsource_reports',
    ],
    'max_boards' => 400,
    'max_cards' => 4000,
    'prefix' => 'MicroPowerManager',
    'boardId'=>'6291424c4e11631cfad78a37',
    'webhookId'=> '629142abb63d648cf1b2d967',
    'card_prefix' => 'micro_power_manager_cards',
    'callback' => getenv('TICKETING_CALLBACK', ''),

];
