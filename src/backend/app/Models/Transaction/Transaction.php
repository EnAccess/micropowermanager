<?php

namespace App\Models\Transaction;

use App\Models\AssetPerson;
use App\Models\Base\BaseModel;
use App\Models\Device;
use App\Models\PaymentHistory;
use App\Models\Sms;
use App\Models\Token;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * General purpose system Transaction.
 *
 * Serves as a normalized abstraction of financial or operational transactions within
 * the MicroPowerManager system. This model is used to track and interact with common
 * system entities such as devices, users, or external transaction sources.
 * It also provides a unified representation of a transaction, decoupling the system
 * from payment provider-specific or manufacturer-specific transaction formats.
 *
 * The `originalTransaction()` method links this system-level transaction to the
 * payment provider-specific transaction.
 *
 * @property int    $id
 * @property int    $amount
 * @property string $type
 * @property string $sender
 * @property string $message
 * @property string $original_transaction_type
 */
class Transaction extends BaseModel {
    public const RELATION_NAME = 'transaction';
    public const TYPE_IMPORTED = 'imported';

    /**
     * Get the payment provider-specific transaction linked to this system transaction.
     *
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function originalTransaction(): MorphTo {
        return $this->morphTo();
    }

    /**
     * @return HasOne<Token, $this>
     */
    public function token(): HasOne {
        return $this->hasOne(Token::class);
    }

    /**
     * @return MorphOne<Sms, $this>
     */
    public function sms(): MorphOne {
        return $this->morphOne(Sms::class, 'trigger');
    }

    /**
     * @return HasMany<PaymentHistory, $this>
     */
    public function paymentHistories(): HasMany {
        return $this->hasMany(PaymentHistory::class);
    }

    /**
     * @return HasOne<Device, $this>
     */
    public function device(): HasOne {
        return $this->hasOne(Device::class, 'device_serial', 'message');
    }

    /**
     * @return HasOne<AssetPerson, $this>
     */
    public function appliance(): HasOne {
        return $this->hasOne(AssetPerson::class, 'device_serial', 'message');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function periodTargetAlternative(int $cityId, string $startDate, string $endDate): array {
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
                LEFT JOIN devices
                    ON  devices.device_serial = transactions.message
                LEFT JOIN people
                    ON  people.id = devices.person_id
                LEFT JOIN addresses
                    ON addresses.owner_id = people.id AND addresses.owner_type = 'person'
                WHERE
                    DATE(transactions.created_at) BETWEEN :periodStartDate AND :periodEndDate
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

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function getMessage(): string {
        return $this->message;
    }
}
