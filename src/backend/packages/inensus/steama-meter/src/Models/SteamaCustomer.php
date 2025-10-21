<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Base\BaseModel;
use App\Models\Person\Person;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property      int                                 $id
 * @property      int                                 $site_id
 * @property      int                                 $user_type_id
 * @property      int                                 $customer_id
 * @property      int                                 $mpm_customer_id
 * @property      float                               $energy_price
 * @property      float                               $account_balance
 * @property      float                               $low_balance_warning
 * @property      string|null                         $hash
 * @property      Carbon|null                         $created_at
 * @property      Carbon|null                         $updated_at
 * @property-read Person|null                         $mpmPerson
 * @property-read SteamaCustomerBasisPaymentPlan|null $paymentPlans
 * @property-read SteamaSite|null                     $site
 * @property-read Collection<int, SteamaMeter>        $stmMeters
 * @property-read SteamaUserType|null                 $userType
 */
class SteamaCustomer extends BaseModel {
    protected $table = 'steama_customers';

    public function mpmPerson(): BelongsTo {
        return $this->belongsTo(Person::class, 'mpm_customer_id');
    }

    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }

    public function userType(): BelongsTo {
        return $this->belongsTo(SteamaUserType::class, 'user_type_id');
    }

    public function paymentPlans(): HasOne {
        return $this->hasOne(SteamaCustomerBasisPaymentPlan::class);
    }

    public function stmMeters(): HasMany {
        return $this->hasMany(SteamaMeter::class, 'customer_id', 'customer_id');
    }
}
