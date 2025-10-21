<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int               $id
 * @property      int               $agent_receipt_id
 * @property      float             $due
 * @property      float             $since_last_visit
 * @property      float             $earlier
 * @property      float             $collected
 * @property      float             $summary
 * @property      Carbon|null       $created_at
 * @property      Carbon|null       $updated_at
 * @property-read AgentReceipt|null $receipt
 */
class AgentReceiptDetail extends BaseModel {
    /**
     * @return BelongsTo<AgentReceipt, $this>
     */
    public function receipt(): BelongsTo {
        return $this->belongsTo(AgentReceipt::class, 'agent_receipt_id');
    }
}
