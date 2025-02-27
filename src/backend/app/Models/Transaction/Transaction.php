<?php

namespace App\Models\Transaction;

use App\Helpers\RelationsManager;
use App\Models\AssetPerson;
use App\Models\Base\BaseModel;
use App\Models\Device;
use App\Models\PaymentHistory;
use App\Models\Sms;
use App\Models\Token;
use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * Class Transaction.
 *
 * @property int    $id
 * @property int    $amount
 * @property string $type
 * @property string $sender
 * @property string $message
 * @property string $original_transaction_type
 */
class Transaction extends BaseModel {
    use HasFactory;
    use RelationsManager;

    public const RELATION_NAME = 'transaction';
    public const TYPE_IMPORTED = 'imported';

    public function originalTransaction(): MorphTo {
        return $this->morphTo();
    }

    public function token(): HasOne {
        return $this->hasOne(Token::class);
    }

    public function sms(): MorphOne {
        return $this->morphOne(Sms::class, 'trigger');
    }

    public function paymentHistories(): HasMany {
        return $this->hasMany(PaymentHistory::class);
    }

    public function device(): HasOne {
        return $this->hasOne(Device::class, 'device_serial', 'message');
    }

    public function appliance(): HasOne {
        return $this->hasOne(AssetPerson::class, 'device_serial', 'message');
    }

    public function periodTargetAlternative($cityId, $startDate, $endDate) {
        $sql = <<<SQL
            SELECT
                SUM(transactions.amount) AS revenue,
                COUNT(transactions.id) AS total,
                AVG(transactions.amount) AS average,
                YEARWEEK(transactions.created_at, 3) AS period
            FROM transactions
            WHERE transactions.id IN (
                SELECT DISTINCT(transactions.id)
                FROM transactions
                LEFT JOIN airtel_transactions
                    ON transactions.original_transaction_id = airtel_transactions.id AND transactions.original_transaction_type = 'airtel_transaction'
                LEFT JOIN vodacom_transactions
                    ON transactions.original_transaction_id = vodacom_transactions.id AND transactions.original_transaction_type = 'vodacom_transaction'
                LEFT JOIN meters
                    ON transactions.message = meters.serial_number
                LEFT JOIN meter_parameters
                    ON meter_parameters.meter_id = meters.id
                LEFT JOIN people
                    ON people.id = meter_parameters.owner_id AND owner_type = 'person'
                LEFT JOIN addresses
                    ON addresses.owner_id = people.id AND addresses.owner_type = 'person'
                WHERE
                    DATE(transactions.created_at) BETWEEN :periodStartDate AND :periodEndDate
                    AND (airtel_transactions.status = 1 OR vodacom_transactions.status = 1)
                    AND addresses.city_id = :city_id
            );
            SQL;
        // FIXME: This used to be here, but no longer.
        // Why was it removed?
        // " GROUP BY CONCAT(YEAR(transactions.created_at), '-', WEEK(transactions.created_at,3))" .
        // " ORDER BY CON CAT(YEAR(transactions.created_at), '-', WEEK(transactions.created_at,3))";

        $sth = DB::connection()->getPdo()->prepare($sql);
        $sth->bindValue(':city_id', $cityId, \PDO::PARAM_INT);
        $sth->bindValue(':periodStartDate', $startDate, \PDO::PARAM_STR);
        $sth->bindValue(':periodEndDate', $endDate, \PDO::PARAM_STR);

        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAmount(): int {
        return $this->amount;
    }

    public function getOriginalTransactionType(): string {
        return $this->original_transaction_type;
    }

    public function getSender(): string {
        return $this->sender;
    }

    public function setAmount(int $amount): void {
        $this->amount = $amount;
    }

    public function setSender(string $sender): void {
        $this->sender = $sender;
    }

    public function setMessage(string $message): void {
        $this->message = $message;
    }

    public function setOriginalTransactionType(string $originalTransaction): void {
        $this->original_transaction_type = $originalTransaction;
    }

    public function getId(): int {
        return $this->id;
    }

    public function setType(string $type) {
        $this->type = $type;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function originalAgent(): BelongsToMorph {
        return BelongsToMorph::build($this, AgentTransaction::class, 'originalTransaction');
    }

    public function originalCash(): BelongsToMorph {
        return BelongsToMorph::build($this, CashTransaction::class, 'originalTransaction');
    }

    public function originalWaveMoney(): BelongsToMorph {
        return BelongsToMorph::build($this, WaveMoneyTransaction::class, 'originalTransaction');
    }
}
