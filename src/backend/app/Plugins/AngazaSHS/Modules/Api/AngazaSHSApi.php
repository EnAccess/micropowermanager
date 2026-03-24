<?php

namespace App\Plugins\AngazaSHS\Modules\Api;

use App\DTO\TransactionDataContainer;
use App\Events\NewLogEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Lib\IManufacturerAPI;
use App\Models\Device;
use App\Models\Token;
use App\Plugins\AngazaSHS\Exceptions\AngazaApiResponseException;
use App\Plugins\AngazaSHS\Models\AngazaCredential;
use App\Plugins\AngazaSHS\Models\AngazaTransaction;
use App\Plugins\AngazaSHS\Services\AngazaCredentialService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AngazaSHSApi implements IManufacturerAPI {
    public const API_CALL_UNIT_CREDIT = '/unit_credit';

    public function __construct(
        private AngazaCredentialService $credentialService,
        private AngazaTransaction $angazaTransaction,
        private ApiRequests $apiRequests,
    ) {}

    public function chargeDevice(TransactionDataContainer $transactionContainer): array {
        $dayDifferenceBetweenTwoInstallments = $transactionContainer->dayDifferenceBetweenTwoInstallments;
        $minimumPurchaseAmount = $transactionContainer->installmentCost;
        $minimumPurchaseAmountPerDay =
            ($minimumPurchaseAmount / $dayDifferenceBetweenTwoInstallments); // This is for 1 day of energy
        $transactionContainer->chargedEnergy = 0; // will represent the day count
        $transactionContainer->chargedEnergy += ceil($transactionContainer->rawAmount / $minimumPurchaseAmountPerDay);

        Log::debug('ENERGY TO BE CHARGED as Day '.$transactionContainer->chargedEnergy.
            ' Manufacturer => AngazaSHSApi');

        $device = $transactionContainer->device;
        $energy = $transactionContainer->chargedEnergy;

        $params = [
            'unit_number' => $device->device_serial,
            'state' => [
                'desired' => [
                    'credit_until_dt' => Carbon::now()->addDays($energy)->toIso8601String(),
                ],
            ],
        ];
        $credentials = $this->credentialService->getCredentials();
        $response = $this->handleApiRequest($credentials, $params);

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, 'Token: '.$response['_embedded']['latest_keycode']['keycode'].' created for '.$energy.' days usage.');

        return [
            'token' => $response['_embedded']['latest_keycode']['keycode'],
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => $energy,
        ];
    }

    /**
     * @return array{token: string, token_type: string, token_unit: null, token_amount: null}
     */
    public function unlockDevice(TransactionDataContainer $transactionContainer): array {
        $device = $transactionContainer->device;

        $params = [
            'unit_number' => $device->device_serial,
            'state' => [
                'desired' => [
                    'credit_until_dt' => 'UNLOCKED',
                ],
            ],
        ];
        $credentials = $this->credentialService->getCredentials();
        $response = $this->handleApiRequest($credentials, $params);

        $token = $response['_embedded']['latest_keycode']['keycode'];

        $this->recordTransaction($transactionContainer);
        $this->logAction($transactionContainer, "Token: {$token} created for unlocking device.");

        return [
            'token' => $token,
            'token_type' => Token::TYPE_UNLOCK,
            'token_unit' => null,
            'token_amount' => null,
        ];
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function handleApiRequest(AngazaCredential $credentials, array $params): array {
        $response = $this->apiRequests->put($credentials, $params, self::API_CALL_UNIT_CREDIT);
        if (isset($response['context'])) {
            throw new AngazaApiResponseException($response['context']['reason']);
        }

        return $response;
    }

    private function recordTransaction(TransactionDataContainer $transactionContainer): void {
        $manufacturerTransaction = $this->angazaTransaction->newQuery()->create([]);
        $transactionContainer->transaction->originalTransaction()->first()->update([
            'manufacturer_transaction_id' => $manufacturerTransaction->id,
            'manufacturer_transaction_type' => 'angaza_transaction',
        ]);
    }

    private function logAction(TransactionDataContainer $transactionContainer, string $action): void {
        event(new NewLogEvent([
            'user_id' => -1,
            'affected' => $transactionContainer->appliancePerson,
            'action' => $action,
        ]));
    }

    /**
     * @return array<string,mixed>|null
     *
     * @throws ApiCallDoesNotSupportedException
     */
    public function clearDevice(Device $device): ?array {
        throw new ApiCallDoesNotSupportedException('This api call does not supported');
    }
}
