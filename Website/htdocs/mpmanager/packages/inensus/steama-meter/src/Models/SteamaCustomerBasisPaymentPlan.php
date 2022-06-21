<?php

namespace Inensus\SteamaMeter\Models;

use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SteamaCustomerBasisPaymentPlan extends BaseModel
{
    protected $table = 'steama_customer_basis_payment_plans';

    public function customer()
    {
        return $this->belongsTo(SteamaCustomer::class, 'customer_id');
    }

    public function paymentPlan()
    {
        return $this->morphTo();
    }

    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanFlatRate()
    {
        return BelongsToMorph::build($this, SteamaFlatRatePaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanHybrid()
    {
        return BelongsToMorph::build($this, SteamaHybridPaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanSubscription()
    {
        return BelongsToMorph::build($this, SteamaSubscriptionPaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanMinimumTopUp()
    {
        return BelongsToMorph::build($this, SteamaMinimumTopUpRequirementsPaymentPlan::class, 'paymentPlan');
    }

    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanAssetRates()
    {
        return BelongsToMorph::build($this, SteamaAssetRatesPaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanPerKwh()
    {
        return BelongsToMorph::build($this, SteamaPerKwhPaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanTariffOverride()
    {
        return BelongsToMorph::build($this, SteamaTariffOverridePaymentPlan::class, 'paymentPlan');
    }
    /**
     * A work-around for querying the polymorphic relation with whereHas
     *
     * @return BelongsTo
     */
    public function paymentPlanCustomerBasisTimeOfUsage()
    {
        return BelongsToMorph::build($this, SteamaCustomerBasisTimeOfUsage::class, 'paymentPlan');
    }
}
