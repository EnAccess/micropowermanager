<?php

namespace App\Models\Transaction;

use App\Models\Agent;
use App\Models\AppliancePerson;
use App\Models\Base\BaseModel;
use App\Models\Device;
use App\Models\PaymentHistory;
use App\Models\Sms;
use App\Models\Token;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
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
 * @property      int                               $id
 * @property      int                               $original_transaction_id
 * @property      string                            $original_transaction_type
 * @property      int|null                          $agent_id
 * @property      float                             $amount
 * @property      string|null                       $type
 * @property      string                            $sender
 * @property      string                            $message
 * @property      Carbon|null                       $created_at
 * @property      Carbon|null                       $updated_at
 * @property-read Agent|null                        $agent
 * @property-read AppliancePerson|null              $appliance
 * @property-read Device|null                       $device
 * @property-read BasePaymentProviderTransaction    $originalTransaction
 * @property-read Collection<int, PaymentHistory>   $paymentHistories
 * @property-read Sms|null                          $sms
 * @property-read Token|null                        $token
 * @property-read OpenPaygoIssuanceReservation|null $openPaygoIssuance
 */
class Transaction extends BaseModel {
    protected $hidden = ['openPaygoIssuance'];

    public const RELATION_NAME = 'transaction';
    public const TYPE_ENERGY = 'energy';
    public const TYPE_DEFERRED_PAYMENT = 'deferred_payment';
    public const TYPE_EAAS_RATE = 'eaas_rate';
    public const TYPE_DOWN_PAYMENT = 'down_payment';
    public const TYPE_UNKNOWN = 'unknown';
    public const TYPE_IMPORTED = 'imported';
    public const TYPE_AD_HOC = 'ad_hoc';

    /**
     * Get the payment provider-specific transaction linked to this system transaction.
     *
     * @return MorphTo<BasePaymentProviderTransaction, $this>
     */
    public function originalTransaction(): MorphTo {
        /** @var MorphTo<BasePaymentProviderTransaction, $this> $relation */
        $relation = $this->morphTo();

        return $relation;
    }

    /**
     * The agent who initiated this transaction, when one did. Set for payments an agent pushed
     * through a payment provider; cash payments express the link through their AgentTransaction.
     *
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return HasOne<Token, $this>
     */
    public function token(): HasOne {
        return $this->hasOne(Token::class);
    }

    /**
     * @return HasOne<OpenPaygoIssuanceReservation, $this>
     */
    public function openPaygoIssuance(): HasOne {
        return $this->hasOne(OpenPaygoIssuanceReservation::class);
    }

    /**
     * @return array{status: string, message: string}|null
     */
    protected function getTokenIssuanceAttribute(): ?array {
        $reservation = $this->openPaygoIssuance;
        $device = $this->device;
        if ($reservation === null && $device?->device?->manufacturer?->api_name !== 'OpenPaygoApi') {
            return null;
        }

        if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_COMPLETED && $this->token?->exists) {
            return ['status' => 'completed', 'message' => 'OpenPAYGO token issued.'];
        }
        if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_UNCERTAIN) {
            return ['status' => 'uncertain', 'message' => 'Token issuance outcome is uncertain. An administrator must reconcile it before retrying.'];
        }
        if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_GENERATED && $reservation->last_error) {
            return ['status' => 'blocked', 'message' => $reservation->last_error];
        }
        if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_BLOCKED) {
            return ['status' => 'blocked', 'message' => $reservation->last_error ?: 'Token issuance is blocked. An administrator must check device configuration and credit before recovery.'];
        }
        if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_WAITING) {
            $blocked = OpenPaygoIssuanceReservation::query()
                ->where('device_id', $reservation->device_id)
                ->where('id', '<', $reservation->id)
                ->where(function ($query): void {
                    $query->whereIn('state', [OpenPaygoIssuanceReservation::STATE_UNCERTAIN, OpenPaygoIssuanceReservation::STATE_BLOCKED])
                        ->orWhere(function ($query): void {
                            $query->where('state', OpenPaygoIssuanceReservation::STATE_GENERATED)->whereNotNull('last_error');
                        });
                })->exists();

            return $blocked
                ? ['status' => 'blocked', 'message' => 'Waiting for an earlier blocked or uncertain token issuance. An administrator must resolve that issuance first.']
                : ['status' => 'pending', 'message' => 'Waiting for an earlier token issuance for this device.'];
        }
        if ($reservation === null) {
            $configuration = $device->openPaygoConfiguration;
            if ($configuration === null) {
                return ['status' => 'blocked', 'message' => 'OpenPAYGO device configuration is missing. Provision the device before recovering this payment.'];
            }
            if (!$configuration->time_divider) {
                return ['status' => 'blocked', 'message' => 'OpenPAYGO device time divider is missing. Provision it before recovering this payment.'];
            }
            if ($configuration->next_counter > 100000) {
                return ['status' => 'blocked', 'message' => 'OpenPAYGO device counter is exhausted. An administrator must resolve it before issuing more credit.'];
            }
        }

        return ['status' => 'pending', 'message' => 'Payment recorded; OpenPAYGO token issuance is pending.'];
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
     * @return HasOne<AppliancePerson, $this>
     */
    public function paygoAppliance(): HasOne {
        return $this->hasOne(AppliancePerson::class, 'device_serial', 'message')->latestOfMany();
    }

    /**
     * @return HasOne<AppliancePerson, $this>
     */
    public function nonPaygoAppliance(): HasOne {
        return $this->hasOne(AppliancePerson::class, 'id', 'message')->latestOfMany();
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
}
