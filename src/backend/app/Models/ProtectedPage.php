<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProtectedPage extends BaseModelCentral {
    use HasFactory;

    protected $table = 'protected_pages';
}
