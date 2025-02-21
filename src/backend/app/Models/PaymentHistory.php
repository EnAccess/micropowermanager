<?php

namespace App\Models;

use App\Models\Base\BaseModel;
use App\Models\Transaction\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class PaymentHistory.
 *
 * @property int amount
 * @property string payment_service
 * @property string sender
 * @property string payment_type
 * @property int transaction_id
 */
class PaymentHistory extends BaseModel {
    public function paidFor(): MorphTo {
        return $this->morphTo();
    }

    public function payer(): MorphTo {
        return $this->morphTo();
    }

    public function transaction(): BelongsTo {
        return $this->belongsTo(Transaction::class);
    }

    public function getFlow(string $payer_type, int $payer_id, string $period, $limit = null, string $order = 'ASC') {
        $sql = <<<SQL
            SELECT
                SUM(amount) AS amount,
                payment_type,
                CONCAT_WS("/", {$period}) AS aperiod
            FROM payment_histories
            WHERE payer_id = :payer_id AND payer_type = :payer_type
            GROUP BY aperiod, payment_type
            ORDER BY aperiod {$order};
            SQL;

        if ($limit !== null) {
            $sql .= ' LIMIT '.(int) $limit;
        }

        return $this->executeSqlCommand($sql, $payer_id, null, $payer_type);
    }

    public function getPaymentFlow(string $payer_type, int $payer_id, int $year) {
        $sql = <<<SQL
            SELECT
                SUM(amount) AS amount,
                MONTH(created_at) AS month
            FROM payment_histories
            WHERE
                payer_id = :payer_id
                AND payer_type = :payer_type
                AND YEAR(created_at) = :year
            GROUP BY MONTH(created_at)
            ORDER BY MONTH(created_at);
            SQL;
        $sth = DB::connection('tenant')->getPdo()->prepare($sql);
        $sth->bindValue(':payer_id', $payer_id, \PDO::PARAM_INT);
        $sth->bindValue(':payer_type', $payer_type, \PDO::PARAM_STR);
        $sth->bindValue(':year', $year);

        $sth->execute();
        $results = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    /**
     * @param Request|array|string $begin
     * @param Request|array|string $end
     */
    public function getOverview($begin, $end) {
        $sql = <<<SQL
            SELECT
                SUM(amount) AS total,
                DATE_FORMAT(created_at, "%Y-%m-%d") AS dato
            FROM payment_histories
            WHERE
                DATE(created_at) >= DATE('{$begin}')
                AND DATE(created_at) <= DATE('{$end}')
            GROUP BY dato;
            SQL;
        $sth = DB::connection('tenant')->getPdo()->prepare($sql);
        $sth->execute();
        $results = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    private function executeSqlCommand(string $sql, $payer_id, $agent_id, $payer_type) {
        $sth = DB::connection('tenant')->getPdo()->prepare($sql);
        if ($payer_id) {
            $sth->bindValue(':payer_id', $payer_id, \PDO::PARAM_INT);
        }
        if ($agent_id) {
            $sth->bindValue(':agent_id', $agent_id, \PDO::PARAM_INT);
        }
        $sth->bindValue(':payer_type', $payer_type, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findCustomersPaidInRange(
        array $customerIds,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): Collection {
        return DB::connection('tenant')->table($this->getTable())
            ->select('payer_id as customer_id')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereIn('payer_id', $customerIds)
            ->where('payer_type', '=', 'person')
            ->groupBy('payer_id')
            ->get();
    }
}
