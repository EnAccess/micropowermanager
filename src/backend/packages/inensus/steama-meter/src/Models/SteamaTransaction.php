<?php

namespace Inensus\SteamaMeter\Models;

use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\BaseManufacturerTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

/**
 * @property AgentTransaction      $agentTransaction
 * @property ThirdPartyTransaction $thirdPartyTransaction
 * @property MesombTransaction     $mesombTransaction
 * @property SwiftaTransaction     $swiftaTransaction
 * @property WaveMoneyTransaction  $waveMoneyTransaction
 */
class SteamaTransaction extends BaseManufacturerTransaction {
    protected $table = 'steama_transactions';

    public function site() {
        return $this->belongsTo(SteamaSite::class, 'site_id', 'site_id');
    }
}
