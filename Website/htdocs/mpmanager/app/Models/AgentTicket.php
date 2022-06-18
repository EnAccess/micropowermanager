<?php

namespace App\Models;

use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Inensus\Ticket\Models\Ticket;

class AgentTicket extends Model
{

    protected $guarded = [];

    public function ticket(): MorphOne
    {
        return $this->morphOne(Ticket::class, 'original_');
    }

    //
}
