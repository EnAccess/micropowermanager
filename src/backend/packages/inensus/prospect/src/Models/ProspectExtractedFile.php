<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $filename
 * @property string|null $file_path
 * @property int         $records_count
 * @property int|null    $file_size
 * @property string|null $extracted_at
 * @property bool        $is_synced
 * @property string|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProspectExtractedFile extends BaseModel {
    protected $table = 'prospect_extracted_files';
    protected $connection = 'tenant';
    protected $fillable = [
        'filename',
        'file_path',
        'records_count',
        'file_size',
        'extracted_at',
        'is_synced',
        'synced_at',
    ];
}
