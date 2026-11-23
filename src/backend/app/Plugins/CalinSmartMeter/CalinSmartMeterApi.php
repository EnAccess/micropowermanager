<?php

namespace App\Plugins\CalinSmartMeter;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\CalinSmartMeter\Exceptions\CalinSmartCreadentialsNotFoundException;
use App\Plugins\CalinSmartMeter\Http\Requests\CalinSmartMeterApiRequests;
use App\Plugins\CalinSmartMeter\Models\CalinSmartCredential;
use App\Plugins\CalinSmartMeter\Models\CalinSmartTransaction;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class CalinSmartMeterApi implements ITokenIssuer {
    private string $rootUrl = '/POS_Purchase/';

    public function __construct(
        protected Client $api,
        private CalinSmartTransaction $calinSmartTransaction,
        private CalinSmartCredential $credentials,
        private CalinSmartMeterApiRequests $calinSmartMeterApiRequests,
    ) {}

    /**
     * @return list<VendingCapability>
     */
    public function capabilities(): array {
        return [
            VendingCapability::AddCredit,
        ];
    }

    public function addCredit(TransactionDataContainer $transactionContainer): array {
        $meter = $transactionContainer->device->device;
        $tariff = $transactionContainer->tariff;
        $transactionContainer->chargeAmount += $transactionContainer->amount / $tariff->total_price;
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;

        Log::critical('ENERGY TO BE CHARGED float '.$transactionContainer->chargeAmount.
            ' Manufacturer => Calin Smart');

        $energy = $transactionContainer->chargeAmount;
        try {
            $credentials = $this->credentials->newQuery()->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new CalinSmartCreadentialsNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        $url = $credentials->api_url.$this->rootUrl;
        $tokenParams = [
            'company_name' => $credentials->company_name,
            'user_name' => $credentials->user_name,
            'password' => $credentials->password,
            'password_vend' => $credentials->password_vend,
            'meter_number' => $meter->serial_number,
            'is_vend_by_unit' => true,
            'amount' => $energy,
        ];
        if (config('app.env') === 'demo' || config('app.env') === 'development') {
            // debug token for development
            $token = '48725997619297311927';
        } else {
            $token = $this->calinSmartMeterApiRequests->post($url, $tokenParams);
        }
        $manufacturerTransaction = $this->calinSmartTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'calin_smart_transaction',
        ]);

        return [
            'token' => $token,
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function unlockPaygo(TransactionDataContainer $transactionContainer): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function resetCredit(Device $device): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearCredit(Device $device): array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
