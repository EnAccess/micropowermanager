<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 14:57
 */

namespace Inensus\Ticket\Models;

/**
 * Class Boards
 * @package Inensus\Ticket\Models
 *
 * @property int $id
 * @property string $board_id
 * @property string $board_name
 * @property string $web_hook_id
 * @property int $active
 */
class Board extends BaseModel
{
    protected $table = 'ticket_boards';

    public function tickets()
    {
        return $this->hasMany(Card::class);
    }

}
