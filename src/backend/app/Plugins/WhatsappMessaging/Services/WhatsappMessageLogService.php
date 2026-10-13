<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @phpstan-type Summary array{
 *     total: int,
 *     delivered_whatsapp: int,
 *     read: int,
 *     fell_back: int,
 *     failed: int,
 *     delivery_rate: float|null,
 *     read_rate: float|null,
 *     top_errors: list<array{code: int, title: string|null, count: int}>
 * }
 * @phpstan-type SavingsCounts array{
 *     sms_only: int,
 *     whatsapp_delivered: int,
 *     whatsapp_read: int,
 *     fell_back: int,
 *     whatsapp_billable: int
 * }
 * @phpstan-type SavingsCosts array{whatsapp_cost: float|null, estimated_saved: float|null}
 * @phpstan-type Savings array{
 *     from: string,
 *     to: string,
 *     group_by: string,
 *     currency: string|null,
 *     sms_only: int,
 *     whatsapp_delivered: int,
 *     whatsapp_read: int,
 *     fell_back: int,
 *     whatsapp_billable: int,
 *     whatsapp_cost: float|null,
 *     estimated_saved: float|null,
 *     missing: list<string>,
 *     series: list<array<string, int|float|string|null>>
 * }
 */
class WhatsappMessageLogService {
    public const string GROUP_BY_DAY = 'day';
    public const string GROUP_BY_WEEK = 'week';
    public const array SAVINGS_GROUPINGS = [self::GROUP_BY_DAY, self::GROUP_BY_WEEK];

    private const int TOP_ERRORS_LIMIT = 5;

    private const int COST_PRECISION = 4;

    private const array RELATIONS = ['sms', 'contact.person'];

    private const array EMPTY_SAVINGS_COUNTS = [
        'sms_only' => 0,
        'whatsapp_delivered' => 0,
        'whatsapp_read' => 0,
        'fell_back' => 0,
        'whatsapp_billable' => 0,
    ];

    public function __construct(
        private WhatsappMessage $whatsappMessage,
        private Sms $sms,
        private WhatsappCredentialService $credentialService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, WhatsappMessage>
     */
    public function paginate(?string $status, ?string $outcome, ?string $phone, ?string $from, ?string $to, int $perPage): LengthAwarePaginator {
        $phoneDigits = $phone === null ? '' : (string) preg_replace('/\D/', '', $phone);

        return $this->periodQuery($from, $to)
            ->withOutcome()
            ->with(self::RELATIONS)
            ->when($status !== null, fn (Builder $query) => $query->where('whatsapp_messages.status', $status))
            ->when($outcome !== null, fn (Builder $query) => $query->whereOutcome((string) $outcome))
            ->when($phoneDigits !== '', fn (Builder $query) => $query->where('sms.receiver', 'like', '%'.$phoneDigits.'%'))
            ->orderByDesc('whatsapp_messages.id')
            ->paginate($perPage);
    }

    public function find(WhatsappMessage $whatsappMessage): WhatsappMessage {
        return $this->periodQuery(null, null)
            ->withOutcome()
            ->with(self::RELATIONS)
            ->whereKey($whatsappMessage->id)
            ->firstOrFail();
    }

    /**
     * @return Summary
     */
    public function summary(?string $from, ?string $to): array {
        $counts = $this->selectOutcomeCounts($this->periodQuery($from, $to)->toBase())
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(('.WhatsappMessage::outcomeExpression().') = ?), 0) AS failed', [WhatsappMessage::OUTCOME_FAILED])
            ->first();

        $total = (int) $counts->total;
        $deliveredWhatsapp = (int) $counts->delivered_whatsapp;
        $read = (int) $counts->read;

        return [
            'total' => $total,
            'delivered_whatsapp' => $deliveredWhatsapp,
            'read' => $read,
            'fell_back' => (int) $counts->fell_back,
            'failed' => (int) $counts->failed,
            'delivery_rate' => $this->rate($deliveredWhatsapp, $total),
            'read_rate' => $this->rate($read, $total),
            'top_errors' => $this->topErrors($from, $to),
        ];
    }

    /**
     * Counts per channel for every day or week of the period, plus the estimated saving.
     * A cost is null, and named in "missing", while the operator has not entered the unit cost it needs.
     *
     * @return Savings
     */
    public function savings(string $from, string $to, string $groupBy): array {
        $credential = $this->credentialService->getCredentials();
        $smsUnitCost = $credential?->sms_unit_cost;
        $whatsappUtilityCost = $credential?->whatsapp_utility_cost;

        $buckets = $this->emptySavingsBuckets($from, $to, $groupBy);

        $whatsappDays = $this->selectOutcomeCounts($this->periodQuery($from, $to)->toBase())
            ->selectRaw('DATE(whatsapp_messages.created_at) AS day')
            ->selectRaw('COALESCE(SUM(whatsapp_messages.billable = 1), 0) AS billable')
            ->groupByRaw('DATE(whatsapp_messages.created_at)')
            ->get();
        foreach ($whatsappDays as $day) {
            $bucket = $this->savingsBucketKey((string) $day->day, $groupBy);
            $buckets[$bucket]['whatsapp_delivered'] += (int) $day->delivered_whatsapp;
            $buckets[$bucket]['whatsapp_read'] += (int) $day->read;
            $buckets[$bucket]['fell_back'] += (int) $day->fell_back;
            $buckets[$bucket]['whatsapp_billable'] += (int) $day->billable;
        }

        foreach ($this->smsOnlyDays($from, $to) as $day) {
            $buckets[$this->savingsBucketKey((string) $day->day, $groupBy)]['sms_only'] += (int) $day->sms_only;
        }

        $totals = self::EMPTY_SAVINGS_COUNTS;
        $series = [];
        foreach ($buckets as $period => $counts) {
            foreach ($counts as $key => $count) {
                $totals[$key] += $count;
            }
            $series[] = ['period' => $period, ...$counts, ...$this->savingsCosts($counts, $smsUnitCost, $whatsappUtilityCost)];
        }

        $missing = array_keys(array_filter(
            ['sms_unit_cost' => $smsUnitCost, 'whatsapp_utility_cost' => $whatsappUtilityCost],
            fn (?float $cost): bool => $cost === null,
        ));

        return [
            'from' => $from,
            'to' => $to,
            'group_by' => $groupBy,
            'currency' => $credential?->cost_currency,
            ...$totals,
            ...$this->savingsCosts($totals, $smsUnitCost, $whatsappUtilityCost),
            'missing' => $missing,
            'series' => $series,
        ];
    }

    /**
     * The inner join on sms leaves out WhatsApp messages that no sms row stands for.
     *
     * @return Builder<WhatsappMessage>
     */
    private function periodQuery(?string $from, ?string $to): Builder {
        return $this->whatsappMessage->newQuery()
            ->joinSms()
            ->when($from !== null, fn (Builder $query) => $query->whereDate('whatsapp_messages.created_at', '>=', (string) $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('whatsapp_messages.created_at', '<=', (string) $to));
    }

    private function selectOutcomeCounts(QueryBuilder $query): QueryBuilder {
        $outcome = WhatsappMessage::outcomeExpression();

        return $query
            ->selectRaw("COALESCE(SUM(({$outcome}) = ?), 0) AS delivered_whatsapp", [WhatsappMessage::OUTCOME_DELIVERED_WHATSAPP])
            ->selectRaw('COALESCE(SUM(whatsapp_messages.status = ?), 0) AS `read`', [WhatsappMessage::STATUS_READ])
            ->selectRaw("COALESCE(SUM(({$outcome}) = ?), 0) AS fell_back", [WhatsappMessage::OUTCOME_FELL_BACK]);
    }

    /**
     * Outgoing sms rows an SMS gateway sent without any WhatsApp attempt.
     *
     * @return iterable<object{day: string, sms_only: int|string}>
     */
    private function smsOnlyDays(string $from, string $to): iterable {
        return $this->sms->newQuery()
            ->toBase()
            ->where('sms.direction', Sms::DIRECTION_OUTGOING)
            ->whereIn('sms.status', [Sms::STATUS_SENT, Sms::STATUS_DELIVERED])
            ->whereNotNull('sms.gateway_id')
            ->where('sms.gateway_id', '<>', MpmPlugin::WHATSAPP_MESSAGING)
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')
                ->from('whatsapp_messages')
                ->whereColumn('whatsapp_messages.sms_id', 'sms.id'))
            ->whereDate('sms.created_at', '>=', $from)
            ->whereDate('sms.created_at', '<=', $to)
            ->selectRaw('DATE(sms.created_at) AS day, COUNT(*) AS sms_only')
            ->groupByRaw('DATE(sms.created_at)')
            ->get();
    }

    /**
     * Every bucket of the period, zero-filled so a chart shows days without messages.
     *
     * @return array<string, SavingsCounts>
     */
    private function emptySavingsBuckets(string $from, string $to, string $groupBy): array {
        $end = CarbonImmutable::parse($to);
        $cursor = CarbonImmutable::parse($this->savingsBucketKey($from, $groupBy));

        $buckets = [];
        while ($cursor->lessThanOrEqualTo($end)) {
            $buckets[$cursor->toDateString()] = self::EMPTY_SAVINGS_COUNTS;
            $cursor = $groupBy === self::GROUP_BY_WEEK ? $cursor->addWeek() : $cursor->addDay();
        }

        return $buckets;
    }

    /**
     * A week bucket is named after its Monday, which may lie before the period's first day.
     */
    private function savingsBucketKey(string $day, string $groupBy): string {
        $date = CarbonImmutable::parse($day);

        return ($groupBy === self::GROUP_BY_WEEK ? $date->startOfWeek(CarbonImmutable::MONDAY) : $date)->toDateString();
    }

    /**
     * Meta only charges billable messages, while every WhatsApp-delivered message is an SMS that was not paid for.
     *
     * @param SavingsCounts $counts
     *
     * @return SavingsCosts
     */
    private function savingsCosts(array $counts, ?float $smsUnitCost, ?float $whatsappUtilityCost): array {
        $whatsappCost = $whatsappUtilityCost === null ? null : round($counts['whatsapp_billable'] * $whatsappUtilityCost, self::COST_PRECISION);
        $estimatedSaved = $smsUnitCost === null || $whatsappCost === null
            ? null
            : round($counts['whatsapp_delivered'] * $smsUnitCost - $whatsappCost, self::COST_PRECISION);

        return ['whatsapp_cost' => $whatsappCost, 'estimated_saved' => $estimatedSaved];
    }

    /**
     * @return list<array{code: int, title: string|null, count: int}>
     */
    private function topErrors(?string $from, ?string $to): array {
        return $this->periodQuery($from, $to)
            ->toBase()
            ->where('whatsapp_messages.status', WhatsappMessage::STATUS_FAILED)
            ->whereNotNull('whatsapp_messages.error_code')
            ->selectRaw('whatsapp_messages.error_code AS code, MAX(whatsapp_messages.error_message) AS title, COUNT(*) AS error_count')
            ->groupBy('whatsapp_messages.error_code')
            ->orderByDesc('error_count')
            ->limit(self::TOP_ERRORS_LIMIT)
            ->get()
            ->map(fn (object $row): array => [
                'code' => (int) $row->code,
                'title' => $row->title,
                'count' => (int) $row->error_count,
            ])
            ->values()
            ->all();
    }

    private function rate(int $count, int $total): ?float {
        return $total === 0 ? null : round($count / $total, 2);
    }
}
