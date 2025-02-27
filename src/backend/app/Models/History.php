<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class History holds all the changes in the system for the current day.
 *
 * @property int    $id
 * @property string $action   the type of the action like created, updated, deleted/closed
 * @property string $field    the affected field. Only required for the update action
 * @property string $content;
 */
class History extends BaseModel {
    public const ACTION_CREATED = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';

    public function target(): MorphTo {
        return $this->morphTo();
    }
}
