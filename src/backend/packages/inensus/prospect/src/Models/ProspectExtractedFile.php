<?php

namespace Inensus\Prospect\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property string      $filename
 * @property string|null $file_path
 * @property int         $records_count
 * @property int|null    $file_size
 * @property Carbon|null $extracted_at
 * @property bool        $is_synced
 * @property Carbon|null $synced_at
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
