<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReceiptDetail extends BaseModel
{
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(AgentReceipt::class, 'agent_receipt_id');
    }
}
