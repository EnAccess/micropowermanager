<?php

namespace App\Plugins\OpenPaygo;

use App\DTO\TransactionDataContainer;
use App\Enums\DeviceType;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Services\OpenPaygoIssuanceService;

class OpenPaygoApi implements IManufacturerAPI {
    public function __construct(private OpenPaygoIssuanceService $issuanceService) {}

    /**
     * @return list<ManufacturerCapability>
     */
    public function capabilities(): array {
        return [
            ManufacturerCapability::UnlockToken,
            ManufacturerCapability::ResetToken,
        ];
    }

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        return $this->tokenData($this->issuePayment($transactionContainer));
    }

    public function issuePayment(TransactionDataContainer $transactionContainer): Token {
        $device = $transactionContainer->device;
        if ($device === null) {
            throw new ApiCallDoesNotSupportedException('OpenPAYGO credit issuance requires a device.');
        }

        if (!in_array($device->device_type, [DeviceType::SolarHomeSystem->value, DeviceType::EBike->value], true)) {
            throw new ApiCallDoesNotSupportedException(
                'OpenPAYGO time tokens are not supported for this device type.'
            );
        }

        $isEnergyService = $transactionContainer->appliancePerson instanceof AppliancePerson
            && $transactionContainer->appliancePerson->isEnergyService();
        if (!$isEnergyService && $transactionContainer->applianceInstallmentsFullFilled) {
            return $this->issueForTransaction($transactionContainer->transaction, $device, 'unlock');
        }

        if (!isset(
            $transactionContainer->amount,
            $transactionContainer->installmentCost,
            $transactionContainer->dayDifferenceBetweenTwoInstallments,
        ) || !is_finite($transactionContainer->amount)
            || !is_finite($transactionContainer->installmentCost)
            || !is_finite($transactionContainer->dayDifferenceBetweenTwoInstallments)
            || $transactionContainer->amount <= 0
            || $transactionContainer->installmentCost <= 0
            || $transactionContainer->dayDifferenceBetweenTwoInstallments <= 0) {
            throw new ApiCallDoesNotSupportedException(
                'OpenPAYGO credit issuance requires a valid payment with MPM-calculated credit days.'
            );
        }

        return $this->issueForTransaction(
            $transactionContainer->transaction,
            $device,
            'credit',
            $transactionContainer->creditDays(),
        );
    }

    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        $device = $transactionContainer->device;
        if ($device === null) {
            throw new ApiCallDoesNotSupportedException('OpenPAYGO unlock requires a device.');
        }

        return $this->tokenData($this->issueForTransaction($transactionContainer->transaction, $device, 'unlock'));
    }

    public function clearDevice(Device $device): ?array {
        throw new ApiCallDoesNotSupportedException('OpenPAYGO reset requires a transaction context.');
    }

    public function issueForTransaction(
        Transaction $transaction,
        ?Device $device,
        string $operation,
        ?float $creditDays = null,
    ): Token {
        if ($device === null) {
            throw new ApiCallDoesNotSupportedException('OpenPAYGO issuance requires a device.');
        }

        if (!in_array($device->device_type, [DeviceType::SolarHomeSystem->value, DeviceType::EBike->value], true)) {
            throw new ApiCallDoesNotSupportedException(
                'OpenPAYGO time tokens are not supported for this device type.'
            );
        }

        return $this->issuanceService->issue($transaction, $device, $operation, $creditDays);
    }

    /**
     * @return array<string, mixed>
     */
    private function tokenData(Token $token): array {
        return [
            'token' => $token->token,
            'token_type' => $token->token_type,
            'token_unit' => $token->token_unit,
            'token_amount' => $token->token_amount,
        ];
    }
}
