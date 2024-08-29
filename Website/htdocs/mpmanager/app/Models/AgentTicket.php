<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Inensus\Ticket\Models\Ticket;

class AgentTicket extends BaseModel
{
    protected $guarded = [];

    public function ticket(): MorphOne
    {
        return $this->morphOne(Ticket::class, 'original_');
    }
}
