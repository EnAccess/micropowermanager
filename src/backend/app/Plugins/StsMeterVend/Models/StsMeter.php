<?php

namespace App\Plugins\StsMeterVend\Models;

use App\Models\Base\BaseModel;
use App\Models\Meter\Meter;
use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property      int                             $id
 * @property      int                             $mpm_meter_id
 * @property      string                          $decoder_key
 * @property      StsBaseDate                     $base_date
 * @property      StsEncryptionAlgorithm          $encryption_algorithm
 * @property      int                             $key_expiry_number
 * @property      Carbon|null                     $last_issued_at
 * @property      Carbon|null                     $created_at
 * @property      Carbon|null                     $updated_at
 * @property-read Meter|null                      $mpmMeter
 * @property-read Collection<int, StsTransaction> $transactions
 */
class StsMeter extends BaseModel {
    protected $table = 'sts_meters';
    protected $guarded = [];
    protected $hidden = ['decoder_key'];
    protected $casts = [
        'decoder_key' => 'encrypted',
        'base_date' => StsBaseDate::class,
        'encryption_algorithm' => StsEncryptionAlgorithm::class,
        'last_issued_at' => 'datetime',
    ];

    /** @return BelongsTo<Meter, $this> */
    public function mpmMeter(): BelongsTo {
        return $this->belongsTo(Meter::class, 'mpm_meter_id');
    }

    /** @return HasMany<StsTransaction, $this> */
    public function transactions(): HasMany {
        return $this->hasMany(StsTransaction::class);
    }
}
