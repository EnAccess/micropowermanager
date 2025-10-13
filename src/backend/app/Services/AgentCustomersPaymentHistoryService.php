<?php

namespace App\Services;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;

class AgentCustomersPaymentHistoryService {
    public function __construct(
        private PaymentHistory $paymentHistory,
    ) {}

    /**
     * @return array<string, array<string, float>>
     */
    public function getPaymentFlowByCustomerId(string $period, int $customerId, ?int $limit, string $order = 'ASC'): array {
        $periodParam = strtoupper($period);
        $period = strtoupper($period);

        $period = match ($period) {
            'D' => 'Day(created_at), Month(created_at), Year(created_at)',
            'W' => 'Week(created_at), Year(created_at)',
            'M' => 'Month(created_at), Year(created_at)',
            default => 'Year(created_at)',
        };

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
            $sql .= ' LIMIT '.$limit;
        }

        $payments = $this->executeSqlCommand($sql, $customerId, null, 'person');

        if ($payments === []) {
            return [$periodParam => ['' => 0.0]];
        }

        return $this->preparePaymentFlow($payments);
    }

    /**
     * @return array<string, array<string, float>>
     */
    public function getPaymentFlows(string $period, int $agentId, ?int $limit, string $order = 'ASC'): array {
        $periodParam = strtoupper($period);
        $period = strtoupper($period);

        $period = match ($period) {
            'D' => 'Day(payment_histories.created_at), Month(payment_histories.created_at), Year(payment_histories.created_at)',
            'W' => 'Week(payment_histories.created_at), Year(payment_histories.created_at)',
            'M' => 'Month(payment_histories.created_at), Year(payment_histories.created_at)',
            default => 'Year(payment_histories.created_at)',
        };

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
            $sql .= ' LIMIT '.$limit;
        }

        $payments = $this->executeSqlCommand($sql, null, $agentId, 'person');

        if ($payments === []) {
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
