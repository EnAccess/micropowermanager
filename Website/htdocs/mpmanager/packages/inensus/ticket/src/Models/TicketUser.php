<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 28.08.18
 * Time: 13:20
 */

namespace Inensus\Ticket\Models;

/**
 * Class UserModel
 *
 * @package Inensus\Ticket\Models
 * @property string $user_name
 * @property string $user_tag
 * @property int $out_source
 */
class TicketUser extends BaseModel
{

    protected $table = 'ticket_users';
}
