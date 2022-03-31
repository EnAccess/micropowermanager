<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentReceiptDetail extends BaseModel
{
    protected $connection = 'test_company_db';
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(AgentReceipt::Class, 'agent_receipt_id');
    }
}
