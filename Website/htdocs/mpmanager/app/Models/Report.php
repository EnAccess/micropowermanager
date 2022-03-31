<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Report
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property string $date
 * @property string $path
 * @property string $type
 */
class Report extends BaseModel
{
    protected $connection = 'test_company_db';


}
