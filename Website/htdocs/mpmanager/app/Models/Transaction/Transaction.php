<?php

namespace App\Models\Transaction;

use App\Helpers\RelationsManager;
use App\Models\BaseModel;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterToken;
use App\Models\PaymentHistory;
use App\Models\Sms;
use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use PDO;

/**
 * Class Transaction
 *
 * @package  App
 * @property integer $id
 * @property integer $amount
 * @property string $type
 * @property string $sender
 * @property string $message
 * @property string $original_transaction_type
 */

class Transaction extends BaseModel
{
    use RelationsManager;

    public const TYPE_IMPORTED = 'imported';

    public function originalTransaction(): morphTo
    {
        return $this->morphTo();
    }

    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsToMorph
     */
    /*    public function originalVodacom(): BelongsToMorph
        {
            return BelongsToMorph::build($this, VodacomTransaction::class, 'originalTransaction');
        }*/


    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsToMorph
     */
    /*    public function originalAirtel(): BelongsToMorph
        {

            return BelongsToMorph::build($this, AirtelTransaction::class, 'originalTransaction');
        }*/

    public function originalAgent(): BelongsToMorph
    {
        return BelongsToMorph::build($this, AgentTransaction::class, 'originalTransaction');
    }

    /* public function originalThirdParty(): BelongsToMorph
        {
            return BelongsToMorph::build($this, ThirdPartyTransaction::class, 'originalTransaction');
        }*/

    public function originalCash(): BelongsToMorph
    {
        return BelongsToMorph::build($this, CashTransaction::class, 'originalTransaction');
    }

    public function originalWaveMoney(): BelongsToMorph
    {
        return BelongsToMorph::build($this, WaveMoneyTransaction::class, 'originalTransaction');
    }

    public function token(): HasOne
    {
        return $this->hasOne(MeterToken::class);
    }

    public function sms(): MorphOne
    {
        return $this->morphOne(Sms::class, 'trigger');
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function meter(): HasOne
    {
        return $this->hasOne(Meter::class, 'serial_number', 'message');
    }


    public function periodTargetAlternative($cityId, $startDate, $endDate)
    {

        $sql = "SELECT sum(transactions.amount) as revenue," .
            " count(transactions.id) as total," .
            " AVG(transactions.amount) as average," .
            " YEARWEEK(transactions.created_at,3) as period" .
            " from transactions" .
            " WHERE transactions.id in (" .
            " SELECT DISTINCT(transactions.id) " .
            " from transactions" .
            " LEFT join airtel_transactions on transactions.original_transaction_id = airtel_transactions.id and" .
            " transactions.original_transaction_type = 'airtel_transaction'" .
            " LEFT join vodacom_transactions on transactions.original_transaction_id = vodacom_transactions.id and" .
            " transactions.original_transaction_type = 'vodacom_transaction'" .
            " LEFT join meters on transactions.message = meters.serial_number" .
            " LEFT JOIN meter_parameters on meter_parameters.meter_id = meters.id" .
            " LEFT JOIN people on people.id = meter_parameters.owner_id and owner_type = 'person'" .
            " LEFT JOIN addresses on addresses.owner_id = people.id and addresses.owner_type = 'person'" .
            " WHERE DATE(transactions.created_at) BETWEEN :periodStartDate and :periodEndDate" .
            " AND (airtel_transactions.status = 1 or vodacom_transactions.status = 1)" .
            " AND addresses.city_id = :city_id " .
            ")";
        //" GROUP BY CONCAT(YEAR(transactions.created_at), '-', WEEK(transactions.created_at,3))" .
        //" ORDER BY CON CAT(YEAR(transactions.created_at), '-', WEEK(transactions.created_at,3))";


        $sth = DB::connection()->getPdo()->prepare($sql);
        $sth->bindValue(':city_id', $cityId, PDO::PARAM_INT);
        $sth->bindValue(':periodStartDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':periodEndDate', $endDate, PDO::PARAM_STR);

        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getOriginalTransactionType(): string
    {
        return $this->original_transaction_type;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function setSender(string $sender): void
    {
        $this->sender= $sender;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function setOriginalTransactionType(string $originalTransaction): void
    {
        $this->original_transaction_type = $originalTransaction;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setType(string $type)
    {
        $this->type =$type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

}
