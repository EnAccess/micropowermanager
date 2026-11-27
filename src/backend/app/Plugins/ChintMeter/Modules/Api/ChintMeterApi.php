<?php

namespace App\Plugins\ChintMeter\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Enums\VendingCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\ITokenIssuer;
use App\Models\Device;
use App\Models\Tariff;
use App\Models\Token;
use App\Plugins\ChintMeter\Enums\TariffAppliedBy;
use App\Plugins\ChintMeter\Exceptions\ChintApiResponseException;
use App\Plugins\ChintMeter\Exceptions\TariffPriceDoesNotMatchException;
use App\Plugins\ChintMeter\Models\ChintCredential;
use App\Plugins\ChintMeter\Models\ChintTransaction;
use App\Plugins\ChintMeter\Services\ChintCredentialService;

class ChintMeterApi implements ITokenIssuer {
    private const string DEBUG_TOKEN = 'debug-token';
    private const float TARIFF_PRICE_TOLERANCE = 0.01;

    public function __construct(
        private ChintCredentialService $credentialService,
        private ChintTransaction $chintTransaction,
        private ChintSoap $chintSoap,
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
        $energy = $this->vend($transactionContainer);

        $transactionContainer->chargeAmount += $energy['amount'];
        $transactionContainer->chargeUnit = Token::UNIT_KWH;
        $transactionContainer->chargeType = Token::TYPE_ENERGY;

        $manufacturerTransaction = $this->chintTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'chint_transaction',
        ]);

        return [
            'token' => $energy['token'],
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => $energy['amount'],
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

    /**
     * @return array{token: string, amount: float}
     */
    private function vend(TransactionDataContainer $transactionContainer): array {
        $tariff = $transactionContainer->tariff;
        $paidAmount = $transactionContainer->amount;

        if (in_array(config('app.env'), ['demo', 'development'], true)) {
            return ['token' => self::DEBUG_TOKEN, 'amount' => $this->energyAtMpmTariff($paidAmount, $tariff)];
        }

        $credentials = $this->credentialService->getCredentials();
        if (!$credentials instanceof ChintCredential || $credentials->user_name === null || $credentials->user_password === null) {
            throw new ChintApiResponseException('Chint credentials are not configured.');
        }

        $customerId = $transactionContainer->device->device->serial_number;
        $chintTariffPrice = $this->chintSoap->validation($credentials, $customerId)['tariffPrice'];

        // Chint only accepts a money amount and converts it with its own tariff. To issue the energy MPM
        // calculated, that energy is priced back at Chint's tariff.
        $amountToSend = match ($credentials->tariff_applied_by) {
            TariffAppliedBy::ChintPlatform => $this->paidAmountAtMatchingTariff($paidAmount, $chintTariffPrice, $tariff),
            TariffAppliedBy::Mpm => $this->energyAtMpmTariff($paidAmount, $tariff) * $this->positiveChintTariffPrice($chintTariffPrice, $customerId),
        };

        $purchase = $this->chintSoap->transaction($credentials, $customerId, $amountToSend);

        return ['token' => $purchase['rechargeToken'], 'amount' => $purchase['energy']];
    }

    private function energyAtMpmTariff(float $paidAmount, Tariff $tariff): float {
        // Chint issues energy in 0.1 kWh steps.
        return round($paidAmount / $tariff->total_price, 1);
    }

    private function paidAmountAtMatchingTariff(float $paidAmount, float $chintTariffPrice, Tariff $tariff): float {
        if (abs($chintTariffPrice - $tariff->total_price) > self::TARIFF_PRICE_TOLERANCE) {
            throw new TariffPriceDoesNotMatchException("Chint's tariff price ({$chintTariffPrice}) does not match the price of tariff {$tariff->name} ({$tariff->total_price}). Align the two, or let MPM apply the tariff in the Chint Meter settings.");
        }

        return $paidAmount;
    }

    private function positiveChintTariffPrice(float $chintTariffPrice, string $customerId): float {
        if ($chintTariffPrice <= 0) {
            throw new TariffPriceDoesNotMatchException("Chint reported no tariff price for customer {$customerId}, so the energy cannot be converted to a purchase amount.");
        }

        return $chintTariffPrice;
    }
}
