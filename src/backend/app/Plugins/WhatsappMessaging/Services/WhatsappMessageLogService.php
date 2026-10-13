<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
 */
class WhatsappMessageLogService {
    private const int TOP_ERRORS_LIMIT = 5;

    private const array RELATIONS = ['sms', 'contact.person'];

    public function __construct(
        private WhatsappMessage $whatsappMessage,
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
        $outcome = WhatsappMessage::outcomeExpression();

        $counts = $this->periodQuery($from, $to)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COALESCE(SUM(({$outcome}) = ?), 0) AS delivered_whatsapp", [WhatsappMessage::OUTCOME_DELIVERED_WHATSAPP])
            ->selectRaw('COALESCE(SUM(whatsapp_messages.status = ?), 0) AS `read`', [WhatsappMessage::STATUS_READ])
            ->selectRaw("COALESCE(SUM(({$outcome}) = ?), 0) AS fell_back", [WhatsappMessage::OUTCOME_FELL_BACK])
            ->selectRaw("COALESCE(SUM(({$outcome}) = ?), 0) AS failed", [WhatsappMessage::OUTCOME_FAILED])
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
