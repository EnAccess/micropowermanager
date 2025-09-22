<?php

namespace Inensus\Ticket\Models;

use Carbon\Carbon;
use Database\Factories\Inensus\Ticket\Models\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int    $id
 * @property string $creator_type
 * @property int    $creator_id
 * @property int    $assigned_id
 * @property string $owner_type
 * @property int    $owner_id
 * @property int    $status
 * @property Carbon $due_date
 * @property string $title
 * @property string $content
 * @property int    $category_id
 */
class Ticket extends BaseModel {
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

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

    public function ticketsOpenedWithCategories($miniGridId, $startDate = null, $endDate = null): bool|array {
        $dateFilter = '';
        if ($startDate && $endDate) {
            $dateFilter = " AND tickets.created_at BETWEEN '{$startDate}' AND '{$endDate}'";
        }

        $sql = <<<SQL
            SELECT
                ticket_categories.label_name,
                COUNT(tickets.id) AS new_tickets,
                YEARWEEK(tickets.created_at, 3) AS period
            FROM tickets
            LEFT JOIN ticket_categories ON tickets.category_id = ticket_categories.id
            LEFT JOIN addresses ON addresses.owner_id = tickets.owner_id
            WHERE
                addresses.owner_type = 'person'
                AND addresses.city_id IN (
                    SELECT id
                    FROM cities
                    WHERE mini_grid_id = {$miniGridId}
                ){$dateFilter}
            GROUP BY
                ticket_categories.label_name,
                tickets.category_id,
                YEARWEEK(tickets.created_at, 3);
            SQL;

        $sth = DB::connection('tenant')->getPdo()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function ticketsClosedWithCategories($miniGridId, $startDate = null, $endDate = null): bool|array {
        $dateFilter = '';
        if ($startDate && $endDate) {
            $dateFilter = " AND tickets.updated_at BETWEEN '{$startDate}' AND '{$endDate}'";
        }

        $sql = <<<SQL
            SELECT
                ticket_categories.label_name,
                COUNT(tickets.id) AS closed_tickets,
                YEARWEEK(tickets.updated_at, 3) AS period
            FROM tickets
            LEFT JOIN ticket_categories ON tickets.category_id = ticket_categories.id
            LEFT JOIN addresses ON addresses.owner_id = tickets.owner_id
            WHERE
                addresses.owner_type = 'person'
                AND addresses.city_id IN (
                    SELECT id
                    FROM cities
                    WHERE mini_grid_id = {$miniGridId}
                )
                AND tickets.status = 1{$dateFilter}
            GROUP BY
                ticket_categories.label_name,
                tickets.category_id,
                YEARWEEK(tickets.updated_at, 3);
            SQL;

        $sth = DB::connection('tenant')->getPdo()->prepare($sql);

        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function comments(): HasMany {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }
}
