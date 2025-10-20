<?php

namespace Inensus\Ticket\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;
use Database\Factories\Inensus\Ticket\Models\TicketUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class UserModel.
 *
 * @property string $user_name
 * @property string $user_tag
 * @property int    $out_source
 * @property int    $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
