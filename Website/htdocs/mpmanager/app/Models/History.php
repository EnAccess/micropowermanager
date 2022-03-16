<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class History holds all the changes in the system for the current day.
 *
 * @package  App\Models
 * @property int $id
 * @property string $action the type of the action like created, updated, deleted/closed
 * @property string $field  the affected field. Only required for the update action
 * @property string $content;
 */
class History extends Model
{
    public const ACTION_CREATED = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
