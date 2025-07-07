<?php

namespace App\Services;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;

class AgentCustomersPaymentHistoryService {
    public function __construct(
        private PaymentHistory $paymentHistory,
    ) {}

    /**
     * @param string   $period
     * @param int      $customerId
     * @param int|null $limit
     * @param string   $order
     *
     * @return array<string, array<string, float>>
     */
    public function getPaymentFlowByCustomerId(string $period, int $customerId, ?int $limit, string $order = 'ASC'): array {
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

        $sql = <<<SQL
            SELECT
                SUM(amount) AS amount,
                payment_type,
                CONCAT_WS("/", {$period}) AS period
            FROM payment_histories
            WHERE
                payer_id = :payer_id
                AND payer_type = :payer_type
            GROUP BY period, payment_type
            ORDER BY MIN(created_at) {$order};
            SQL;

        if ($limit !== null) {
            $sql .= ' LIMIT '.(int) $limit;
        }

        $payments = $this->executeSqlCommand($sql, $customerId, null, 'person');

        if (empty($payments)) {
            return [$periodParam => ['' => 0.0]];
        }

        return $this->preparePaymentFlow($payments);
    }

    /**
     * @param string   $period
     * @param int      $agentId
     * @param int|null $limit
     * @param string   $order
     *
     * @return array<string, array<string, float>>
     */
    public function getPaymentFlows(string $period, int $agentId, ?int $limit, string $order = 'ASC'): array {
        $periodParam = strtoupper($period);
        $period = strtoupper($period);

        switch ($period) {
            case 'D':
                $period = 'Day(payment_histories.created_at), Month(payment_histories.created_at), Year(payment_histories.created_at)';
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

        $sql = <<<SQL
            SELECT
                SUM(amount) AS amount,
                payment_type,
                CONCAT_WS("/", {$period}) AS period
            FROM payment_histories
            INNER JOIN addresses ON payment_histories.payer_id = addresses.owner_id
            INNER JOIN cities ON addresses.city_id = cities.id
            INNER JOIN mini_grids ON cities.mini_grid_id = mini_grids.id
            INNER JOIN agents ON agents.mini_grid_id = mini_grids.id
            WHERE
                payment_service LIKE '%agent%'
                AND payer_type = :payer_type
                AND agents.id = :agent_id
                AND addresses.is_primary = 1
            GROUP BY period, payment_type
            ORDER BY MIN(payment_histories.created_at) {$order};
            SQL;

        if ($limit !== null) {
            $sql .= ' LIMIT '.(int) $limit;
        }

        $payments = $this->executeSqlCommand($sql, null, $agentId, 'person');

        if (empty($payments)) {
            return [$periodParam => ['' => 0.0]];
        }

        return $this->preparePaymentFlow($payments);
    }

    /**
     * @param array<int, array{period: string, payment_type: string, amount: float}> $payments
     *
     * @return array<string, array<string, float>>
     */
    private function preparePaymentFlow(array $payments): array {
        $flowList = [];
        foreach ($payments as $payment) {
            $flowList[$payment['period']][$payment['payment_type']] = (float) $payment['amount'];
        }

        return $flowList;
    }

    /**
     * @param string   $sql
     * @param int|null $payerId
     * @param int|null $agentId
     * @param string   $payerType
     *
     * @return array<int, array<string, mixed>>
     */
    private function executeSqlCommand(string $sql, ?int $payerId, ?int $agentId, string $payerType): array {
        $sth = DB::connection($this->paymentHistory->getConnectionName())->getPdo()->prepare($sql);

        if ($payerId !== null) {
            $sth->bindValue(':payer_id', $payerId, \PDO::PARAM_INT);
        }

        if ($agentId !== null) {
            $sth->bindValue(':agent_id', $agentId, \PDO::PARAM_INT);
        }

        $sth->bindValue(':payer_type', $payerType, \PDO::PARAM_STR);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }
}
