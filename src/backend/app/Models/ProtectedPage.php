<?php

namespace App\Models;

use App\Models\Base\BaseModelCore;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProtectedPage extends BaseModelCore {
    use HasFactory;

    protected $table = 'protected_pages';
}
