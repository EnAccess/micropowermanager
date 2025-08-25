<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class UserModel.
 *
 * @property string $user_name
 * @property string $user_tag
 * @property int    $out_source
 * @property int    $id
 */
class TicketUser extends BaseModel {
    /** @use HasFactory<\Database\Factories\TicketUserFactory> */
    use HasFactory;

    public const TABLE_NAME = 'ticket_users';
    public const COL_USER_ID = 'user_id';
    protected $table = self::TABLE_NAME;

    public function getId(): int {
        return $this->id;
    }
}
