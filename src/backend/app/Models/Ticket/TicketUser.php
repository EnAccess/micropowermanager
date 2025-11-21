<?php

namespace App\Models\Ticket;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class UserModel.
 *
 * @property int         $id
 * @property string      $user_name
 * @property string|null $phone
 * @property int         $out_source
 * @property int|null    $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TicketUser extends BaseModel {
    /** @use HasFactory<TicketUserFactory> */
    use HasFactory;

    public const TABLE_NAME = 'ticket_users';
    public const COL_USER_ID = 'user_id';
    protected $table = self::TABLE_NAME;

    public function getId(): int {
        return $this->id;
    }
}
