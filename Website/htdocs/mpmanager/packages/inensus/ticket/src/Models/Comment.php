<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 16:00
 */

use Inensus\Ticket\Models\BaseModel;
use Inensus\Ticket\Models\Card;

/**
 * Class Comment
 *
 */
class Comment extends BaseModel
{

    function ticket()
    {
        return $this->belongsTo(Card::class);
    }
}
