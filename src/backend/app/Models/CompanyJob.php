<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyJob extends BaseModelCentral {
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILED = -1;

    /**
     * Has one company
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo {
        return $this->belongsTo(Company::class);
    }
}
