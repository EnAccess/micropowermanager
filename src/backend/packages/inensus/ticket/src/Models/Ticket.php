<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class Ticket extends BaseModel {
    protected $table = 'tickets';

    public const STATUS = [
        'opened' => 0,
        'closed' => 1,
        'waiting' => 2,
    ];

    public function category(): BelongsTo {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function owner(): MorphTo {
        return $this->morphTo();
    }

    public function outsource(): HasOne {
        return $this->hasOne(TicketOutsource::class);
    }

    public function assignedTo(): BelongsTo {
        return $this->belongsTo(TicketUser::class, 'assigned_id');
    }

    public function ticketsOpenedInPeriod($startDate, $endDate) {
        return $this->select(DB::raw('COUNT(id) as amount, YEARWEEK(created_at,3) as period'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(created_at,3)'));
    }

    public function ticketsClosedInPeriod($startDate, $endDate) {
        return $this->select(DB::raw('COUNT(id) as amount, YEARWEEK(updated_at,3) as period,avg(timestampdiff(SECOND, created_at, updated_at)) as avgdiff'))
            ->where('status', 1)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(updated_at,3)'));
    }

    public function creator() {
        return $this->morphTo('creator');
    }

    public function ticketsOpenedWithCategories($miniGridId): bool|array {
        $sql = 'SELECT ticket_categories.label_name, count(tickets.id) as new_tickets, YEARWEEK(tickets.created_at,3) as period FROM `tickets` '.
            'LEFT join ticket_categories on tickets.category_id = ticket_categories.id '.
            'left join addresses on addresses.owner_id = tickets.owner_id '.
            "where addresses.owner_type='person' ".
            'and addresses.city_id  in (SELECT id from cities where mini_grid_id  ='.$miniGridId.' ) '.
            ' GROUP by ticket_categories.label_name, tickets.category_id, YEARWEEK(tickets.updated_at,3), tickets.created_at';

        $sth = DB::connection('tenant')->getPdo()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function ticketsClosedWithCategories($miniGridId): bool|array {
        $sql = 'SELECT ticket_categories.label_name, count(tickets.id) as closed_tickets, YEARWEEK(tickets.updated_at,3) as period FROM `tickets` '.
            'LEFT join ticket_categories on tickets.category_id = ticket_categories.id '.
            'left join addresses on addresses.owner_id = tickets.owner_id '.
            "where addresses.owner_type='person' ".
            'and addresses.city_id in (SELECT id from cities where id =  '.$miniGridId.' ) '.
            'and tickets.status = 1 '.
            'GROUP by ticket_categories.label_name, tickets.category_id,YEARWEEK(tickets.updated_at,3)';
        $sth = DB::connection('tenant')->getPdo()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function comments(): HasMany {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }
}
