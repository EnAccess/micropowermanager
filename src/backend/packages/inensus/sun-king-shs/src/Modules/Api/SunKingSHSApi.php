<?php

namespace Inensus\SunKingSHS\Modules\Api;

use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Misc\TransactionDataContainer;
use App\Models\Device;
use App\Models\Token;
use Illuminate\Support\Facades\Log;
use Inensus\SunKingSHS\Exceptions\SunKingApiResponseException;
use Inensus\SunKingSHS\Models\SunKingTransaction;
use Inensus\SunKingSHS\Services\SunKingCredentialService;

class SunKingSHSApi implements IManufacturerAPI {
    public const API_CALL_TOKEN_GENERATION = '/token';
    public const COMMAND_ADD_CREDIT = 'add_credit';
    public const COMMAND_UNLOCK_DEVICE = 'unlock';

    public function __construct(
        private SunKingCredentialService $credentialService,
        private SunKingTransaction $sunKingTransaction,
        private ApiRequests $apiRequests,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay = ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        Log::debug('ENERGY TO BE CHARGED as Day '.$transactionContainer->chargedEnergy.
            ' Manufacturer => SunKingSHSApi');

        $energy = $transactionContainer->chargedEnergy;
        $params = $this->buildParams($transactionContainer);
        $credentials = $this->credentialService->getCredentials();
        $response = $this->handleApiRequest($credentials, $params);

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, $response['token']);

        return [
            'token' => $response['token'],
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
        ];
    }

    private function buildParams(TransactionDataContainer $transactionContainer): array {
        $deviceSerial = $transactionContainer->device->device_serial;

        if (!$this->isInstallmentsCompleted($transactionContainer)) {
            return [
                'device' => $deviceSerial,
                'command' => self::COMMAND_ADD_CREDIT,
                'payload' => $transactionContainer->chargedEnergy,
                'time_unit' => 'day',
            ];
        }

        return [
            'device' => $deviceSerial,
            'command' => self::COMMAND_UNLOCK_DEVICE,
        ];
    }

    private function handleApiRequest(&$credentials, array $params): array {
        try {
            $authResponse = $this->apiRequests->authentication($credentials);
            $this->credentialService->updateCredentials($credentials, $authResponse);

            return $this->apiRequests->post($credentials, $params, self::API_CALL_TOKEN_GENERATION);
        } catch (SunKingApiResponseException $e) {
            $this->credentialService->updateCredentials($credentials, [
                'access_token' => null,
                'token_expires_in' => null,
            ]);

            throw new SunKingApiResponseException($e->getMessage());
        }
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->sunKingTransaction->newQuery()->create([]);

        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'sun_king_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $token): void {
        $isInstallmentsCompleted = $this->isInstallmentsCompleted($transactionContainer);
        $energy = $transactionContainer->chargedEnergy;
        $action = $isInstallmentsCompleted
            ? "Token: $token created for unlocking device."
            : "Token: $token created for $energy days usage.";

        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    private function isInstallmentsCompleted(TransactionDataContainer $transactionContainer): bool {
        return $transactionContainer->applianceInstallmentsFullFilled;
    }

    public function clearDevice(Device $device) {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
