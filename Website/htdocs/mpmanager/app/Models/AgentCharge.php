<?php

namespace App\Models;

use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class AgentCharge extends BaseModel
{
    //

    public function agent(): void
    {
        $this->belongsTo(Agent::class);
    }

    public function user(): void
    {
        $this->belongsTo(User::class);
    }
    public function history(): MorphOne
    {
        return $this->morphOne(AgentBalanceHistory::class, 'trigger');
    }
}
