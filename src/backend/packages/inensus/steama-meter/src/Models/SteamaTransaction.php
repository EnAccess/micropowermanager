<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * @property      int                        $id
 * @property      int                        $site_id
 * @property      int                        $transaction_id
 * @property      int                        $customer_id
 * @property      float                      $amount
 * @property      string                     $category
 * @property      string                     $provider
 * @property      string                     $timestamp
 * @property      string                     $synchronization_status
 * @property      Carbon|null                $created_at
 * @property      Carbon|null                $updated_at
 * @property-read AgentTransaction|null      $agentTransaction
 * @property-read MesombTransaction|null     $mesombTransaction
 * @property-read SteamaSite|null            $site
 * @property-read SwiftaTransaction|null     $swiftaTransaction
 * @property-read ThirdPartyTransaction|null $thirdPartyTransaction
 * @property-read WaveMoneyTransaction|null  $waveMoneyTransaction
 */
class SteamaTransaction extends BaseManufacturerTransaction {
    protected $table = 'steama_transactions';

    /**
     * @return BelongsTo<SteamaSite, $this>
     */
    public function site(): BelongsTo {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
}
