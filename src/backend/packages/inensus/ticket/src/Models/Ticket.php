<?php

namespace Inensus\Ticket\Models;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property      int                            $id
 * @property      string                         $creator_type
 * @property      int                            $creator_id
 * @property      int|null                       $assigned_id
 * @property      string                         $owner_type
 * @property      int                            $owner_id
 * @property      int                            $status
 * @property      string|null                    $due_date
 * @property      string                         $title
 * @property      string                         $content
 * @property      int                            $category_id
 * @property      Carbon|null                    $created_at
 * @property      Carbon|null                    $updated_at
 * @property-read TicketUser|null                $assignedTo
 * @property-read TicketCategory|null            $category
 * @property-read Collection<int, TicketComment> $comments
 * @property-read Model                          $creator
 * @property-read TicketOutsource|null           $outsource
 * @property-read Model                          $owner
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

    /**
     * @return BelongsTo<TicketCategory, $this>
     */
    public function category(): BelongsTo {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return HasOne<TicketOutsource, $this>
     */
    public function outsource(): HasOne {
        return $this->hasOne(TicketOutsource::class);
    }

    /**
     * @return BelongsTo<TicketUser, $this>
     */
    public function assignedTo(): BelongsTo {
        return $this->belongsTo(TicketUser::class, 'assigned_id');
    }

    /**
     * @return Builder<Ticket>
     */
    public function ticketsOpenedInPeriod(mixed $startDate, mixed $endDate): Builder {
        return $this->select(DB::raw('COUNT(id) as amount, YEARWEEK(created_at,3) as period'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(created_at,3)'));
    }

    /**
     * @return Builder<Ticket>
     */
    public function ticketsClosedInPeriod(mixed $startDate, mixed $endDate): Builder {
        return $this->select(DB::raw('COUNT(id) as amount, YEARWEEK(updated_at,3) as period,avg(timestampdiff(SECOND, created_at, updated_at)) as avgdiff'))
            ->where('status', 1)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(updated_at,3)'));
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function creator() {
        return $this->morphTo('creator');
    }

    /**
     * @return array<int, mixed>
     */
    public function ticketsOpenedWithCategories(int $miniGridId, mixed $startDate = null, mixed $endDate = null): bool|array {
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

    /**
     * @return array<int, mixed>
     */
    public function ticketsClosedWithCategories(int $miniGridId, mixed $startDate = null, mixed $endDate = null): bool|array {
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

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    }
}
