<?php

namespace App\Models\Meter;

use App\Models\AccessRate\AccessRatePayment;
use App\Models\BaseModel;
use App\Models\Manufacturer;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Class Meter
 *
 * @package  App
 * @property string serial_number
 * @property int id;
 * @property int in_use;
 */
class Meter extends BaseModel
{

    protected $guarded = [];
    public static $rules = [
        'serial_number' => 'required|min:1|unique:meters',
        'meter_type_id' => 'exists:meter_types,id',
        'manufacturer_id' => 'exists:manufacturers,id',
    ];


    public function meterType(): BelongsTo
    {
        return $this->belongsTo(MeterType::class);
    }

    public function meterParameter(): HasOne
    {
        return $this->hasOne(MeterParameter::class);
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function meterTariff()
    {
        return $this->meterParameter->tariff;
    }

    public function accessRatePayment(): HasOne
    {
        return $this->hasOne(AccessRatePayment::class);
    }

    public function accessRate()
    {
        return $this->meterParameter->tariff->accessRate;
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(MeterToken::class);
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(MeterConsumption::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'message', 'serial_number');
    }

    public function sumOfTransactions(array $meters, $dateRange = [])
    {
        if (count($meters) === 0) {
            return [['total' => 0]];
        }

        $m = implode(',', $meters);

        $sql = 'select  sum(transactions.amount) as total from transactions
        left join airtel_transactions on transactions.original_transaction_id = airtel_transactions.id
         and transactions.original_transaction_type= \'airtel_transaction\'
        left join vodacom_transactions on transactions.original_transaction_id = vodacom_transactions.id
        and transactions.original_transaction_type= \'vodacom_transaction\'
        where  transactions.message in (' . $m . ')
        and transactions.created_at between  \'' . $dateRange[0] . '\' and \'' . $dateRange[1] . '\'
        and (vodacom_transactions.status = 1 or airtel_transactions.status=1)';


        $sth = DB::connection()->getPdo()->prepare($sql);


        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function averageTransactionPeriod($limit = 50): void
    {
    }
}
