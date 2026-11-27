<?php

namespace App\Plugins\AirtelPaymentProvider\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\AirtelPaymentProvider\Exceptions\AirtelRequestException;
use App\Plugins\AirtelPaymentProvider\Models\AirtelTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use Illuminate\Support\Facades\DB;

/**
 * @extends AbstractPaymentAggregatorTransactionService<AirtelTransaction>
 */
class AirtelTransactionService extends AbstractPaymentAggregatorTransactionService {
    private const array REQUIRED_VALIDATION_FIELDS = ['TYPE', 'CUSTOMERMSISDN', 'MERCHANTMSISDN', 'AMOUNT', 'REFERENCE', 'REFERENCE1'];
    private const int NATIONAL_NUMBER_LENGTH = 9;

    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private AirtelTransaction $airtelTransaction,
        private AirtelCredentialService $credentialService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->airtelTransaction
        );
    }

    /**
     * Checks that the payment Airtel is about to collect can be processed: the meter, its owner
     * and tariff exist and the amount is enough. Leaves the transaction ready to be saved.
     *
     * @param array<string, string> $command
     */
    public function validateCommand(array $command): void {
        foreach (self::REQUIRED_VALIDATION_FIELDS as $field) {
            if (($command[$field] ?? '') === '') {
                throw new AirtelRequestException("The {$field} field is required.");
            }
        }

        if (!is_numeric($command['AMOUNT'])) {
            throw new AirtelRequestException('The AMOUNT field must be a number.');
        }

        if ($this->findByAirtelTransactionId($command['REFERENCE1']) instanceof AirtelTransaction) {
            throw new AirtelRequestException('Duplicate Reference1');
        }

        $amount = (float) $command['AMOUNT'];
        $this->validatePaymentOwner($command['REFERENCE'], $amount);
        $this->payerPhoneNumber = $this->internationalPhoneNumber($command['CUSTOMERMSISDN']);

        $this->imitateTransactionForValidation([
            'amount' => $amount,
            'interface_id' => $command['MERCHANTMSISDN'],
            'business_number' => $command['MERCHANTMSISDN'],
            'status' => AirtelTransaction::STATUS_VALIDATED,
            'trans_id' => '',
            'tr_id' => $command['REFERENCE1'],
        ]);
    }

    /**
     * Airtel repeats the process call when it does not get an answer in time. The row is locked
     * and only a transaction that is still waiting for this call is queued, so a repeated call
     * never produces a second token.
     */
    public function startProcessing(string $airtelTransactionId, string $airtelReference, int $companyId): void {
        DB::connection('tenant')->transaction(function () use ($airtelTransactionId, $airtelReference, $companyId): void {
            $airtelTransaction = $this->airtelTransaction->newQuery()
                ->where('tr_id', $airtelTransactionId)
                ->lockForUpdate()
                ->first();

            if (!$airtelTransaction instanceof AirtelTransaction) {
                throw new AirtelRequestException('Transaction not found');
            }

            if ($airtelTransaction->status !== AirtelTransaction::STATUS_VALIDATED) {
                return;
            }

            $mpmTransaction = $airtelTransaction->transaction()->first();
            if (!$mpmTransaction instanceof Transaction) {
                throw new AirtelRequestException('Transaction not found');
            }

            $airtelTransaction->update([
                'trans_id' => $airtelReference,
                'status' => AirtelTransaction::STATUS_PROCESSING,
            ]);

            dispatch(new ProcessPayment($companyId, $mpmTransaction->id));
        });
    }

    public function findByAirtelTransactionId(string $airtelTransactionId): ?AirtelTransaction {
        return $this->airtelTransaction->newQuery()->where('tr_id', $airtelTransactionId)->first();
    }

    /**
     * Airtel sends the payer's number without the country calling code.
     */
    private function internationalPhoneNumber(string $msisdn): string {
        $digits = (string) preg_replace('/\D/', '', $msisdn);
        $prefix = $this->credentialService->getCredentials()->phone_prefix;

        if (strlen($digits) !== self::NATIONAL_NUMBER_LENGTH || $prefix === null || $prefix === '') {
            return $msisdn;
        }

        return $prefix.$digits;
    }
}
