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
        $sql = 'SELECT sum(amount) as amount, payment_type, CONCAT_WS("/", '.$period.') as aperiod from'.
            ' payment_histories where payer_id=:payer_id and payer_type=:payer_type '.
            'GROUP by aperiod, payment_type ORDER BY created_at  '.$order;

        if ($limit !== null) {
            $sql .= ' limit '.(int) $limit;
        }

        return $this->executeSqlCommand($sql, $payer_id, null, $payer_type);
    }

    public function getAgentCustomersFlow(string $payer_type, $agent_id, string $period, $limit = null, $order = 'ASC') {
        $sql = 'SELECT sum(amount) as amount, payment_type, CONCAT_WS("/", '.$period.') as aperiod '.
            'from payment_histories inner join addresses on payment_histories.payer_id = addresses.owner_id '.
            'inner JOIN cities on addresses.city_id=cities.id inner JOIN mini_grids on '.
            'cities.mini_grid_id=mini_grids.id inner JOIN agents on agents.mini_grid_id=mini_grids.id '.
            ' where payment_service  like \'%agent%\' and payer_type=:payer_type and agents.id=:agent_id '.
            'and addresses.is_primary=1 GROUP by concat( '.$period.'), payment_type ORDER BY '.
            'payment_histories.created_at '.$order;

        if ($limit !== null) {
            $sql .= ' limit '.(int) $limit;
        }

        return $this->executeSqlCommand($sql, null, $agent_id, $payer_type);
    }

    public function getPaymentFlow(string $payer_type, int $payer_id, int $year) {
        $sql = 'SELECT sum(amount) as amount, MONTH(created_at) as month from payment_histories where'.
            ' payer_id=:payer_id and payer_type=:payer_type and '.
            'YEAR(created_at)=:year group by  MONTH(created_at) order  by  MONTH(created_at) ';
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
        $sql = 'SELECT sum(amount) as total, DATE_FORMAT(created_at, "%Y-%m-%d")'.
            ' as dato from payment_histories where  DATE(created_at) >= DATE(\''.$begin.'\') '.
            'and DATE(created_at)<= DATE(\''.$end.'\')  group by dato';
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
