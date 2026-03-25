<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;

/**
 * @property int    $id
 * @property string $queue
 * @property string $connection
 * @property string $payload
 * @property int    $delay_seconds
 * @property int    $attempts
 * @property string $created_at
 */
class PendingJob extends BaseModelCentral {
    public const UPDATED_AT = null;

    protected $table = 'pending_jobs';

    protected $fillable = [
        'queue',
        'connection',
        'payload',
        'delay_seconds',
        'attempts',
    ];
}
