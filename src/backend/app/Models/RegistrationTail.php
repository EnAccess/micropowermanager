<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string|null $component
 * @property bool        $adjusted
 * @property int|null    $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RegistrationTail extends BaseModel {
    protected $table = 'registration_tail';

    /** @var array<string, string> */
    protected $casts = [
        'adjusted' => 'boolean',
    ];
}
