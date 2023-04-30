<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProtectedPage extends MasterModel
{
    use HasFactory;

    protected $table = 'protected_pages';
}
