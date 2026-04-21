<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $provider_name
 * @property string      $template
 * @property string      $pattern
 * @property string|null $sender_pattern
 * @property bool        $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SmsParsingRule extends BaseModel {
    protected $table = 'sms_parsing_rules';

    protected function casts(): array {
        return [
            'enabled' => 'boolean',
        ];
    }
}
