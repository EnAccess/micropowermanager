<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProtectedPage extends BaseModelCentral {
    protected $table = 'protected_pages';
}
