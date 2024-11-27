<?php

namespace App\Services;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;

class AgentCustomersPaymentHistoryService {
    public function __construct(
        private PaymentHistory $paymentHistory,
    ) {}

    public function getPaymentFlowByCustomerId($period, $customerId, $limit, $order = 'ASC') {
        $periodParam = strtoupper($period);
        $period = strtoupper($period);

        switch ($period) {
            case 'D':
                $period = 'Day(created_at), Month(created_at), Year(created_at)';
                break;
            case 'W':
                $period = 'Week(created_at), Year(created_at)';
                break;
            case 'M':
                $period = 'Month(created_at), Year(created_at)';
                break;
            default:
                $period = 'Year(created_at)';
                break;
        }

        $sql = 'SELECT sum(amount) as amount, payment_type, CONCAT_WS("/", '.$period.') as period from'.
            ' payment_histories where payer_id=:payer_id and payer_type=:payer_type '.
            'GROUP by concat( '.$period.'), payment_type ORDER BY created_at  '.$order;

        if ($limit !== null) {
            $sql .= ' limit '.(int) $limit;
        }

        $payments = $this->executeSqlCommand($sql, $customerId, null, 'person');

        if (empty($payments)) {
            $flowList = [];
            $flowList[$periodParam][''] = 0;

            return $flowList;
        }

        return $this->preparePaymentFlow($payments);
    }

    public function getPaymentFlows($period, $agentId, $limit, $order = 'ASC') {
        $periodParam = strtoupper($period);
        $period = strtoupper($period);

        switch ($period) {
            case 'D':
                $period = 'Day(payment_histories.created_at), '.
                    'Month(payment_histories.created_at), Year(payment_histories.created_at)';
                break;
            case 'W':
                $period = 'Week(payment_histories.created_at), Year(payment_histories.created_at)';
                break;
            case 'M':
                $period = 'Month(payment_histories.created_at), Year(payment_histories.created_at)';
                break;
            default:
                $period = 'Year(payment_histories.created_at)';
                break;
        }

        $sql = 'SELECT sum(amount) as amount, payment_type, CONCAT_WS("/", '.$period.') as period '.
            'from payment_histories inner join addresses on payment_histories.payer_id = addresses.owner_id '.
            'inner JOIN cities on addresses.city_id=cities.id inner JOIN mini_grids on '.
            'cities.mini_grid_id=mini_grids.id inner JOIN agents on agents.mini_grid_id=mini_grids.id '.
            ' where payment_service  like \'%agent%\' and payer_type=:payer_type and agents.id=:agent_id '.
            'and addresses.is_primary=1 GROUP by concat( '.$period.'), payment_type ORDER BY '.
            'payment_histories.created_at '.$order;

        if ($limit !== null) {
            $sql .= ' limit '.(int) $limit;
        }
        $payments = $this->executeSqlCommand($sql, null, $agentId, 'person');

        if (empty($payments)) {
            $flowList = [];
            $flowList[$periodParam][''] = 0;

            return $flowList;
        }

        return $this->preparePaymentFlow($payments);
    }

    private function preparePaymentFlow($payments): array {
        $flowList = [];
        foreach ($payments as $payment) {
            $flowList[$payment['period']][$payment['payment_type']] = $payment['amount'];
        }

        return $flowList;
    }

    private function executeSqlCommand(string $sql, $payerId, $agentId, $payerType) {
        $sth = DB::connection($this->paymentHistory->getConnectionName())->getPdo()->prepare($sql);

        if ($payerId) {
            $sth->bindValue(':payer_id', $payerId, \PDO::PARAM_INT);
        }

        if ($agentId) {
            $sth->bindValue(':agent_id', $agentId, \PDO::PARAM_INT);
        }

        $sth->bindValue(':payer_type', $payerType, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
