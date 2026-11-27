<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use App\Plugins\AirtelPaymentProvider\Models\AirtelCredential;
use App\Plugins\AirtelPaymentProvider\Models\AirtelTransaction;
use App\Plugins\AirtelPaymentProvider\Providers\AirtelTransactionProvider;
use App\Plugins\AirtelPaymentProvider\Services\AirtelCredentialService;
use App\Providers\Helpers\TransactionAdapter;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AirtelPaymentProviderTest extends TestCase {
    private const string SECRET = 'a-secret-agreed-with-airtel';
    private const string METER_SERIAL = '47000056460';
    private const string AIRTEL_TRANSACTION_ID = 'MP261126.1030.A12345';

    protected function setUp(): void {
        parent::setUp();

        Queue::fake();
        $this->app->make(AirtelCredentialService::class)
            ->updateCredentials(['jwt_secret' => self::SECRET, 'phone_prefix' => '+255']);
        $this->seedMeter(tariffPrice: 1830, minimumPurchaseAmount: 1000);
    }

    public function testAPaymentIsValidatedProcessedAndReportedAsSuccessful(): void {
        $this->callValidation()->assertOk();
        $this->assertXml($this->callValidation(transactionId: 'OTHER.1'), 200, 'Success');
        $airtelTransaction = AirtelTransaction::query()->where('tr_id', self::AIRTEL_TRANSACTION_ID)->firstOrFail();
        $this->assertSame(AirtelTransaction::STATUS_VALIDATED, $airtelTransaction->status);
        $this->assertXml($this->callEnquiry(), 404, 'Not Found');

        $response = $this->callProcess();

        $this->assertXml($response, 200, 'Success');
        $this->assertSame(self::AIRTEL_TRANSACTION_ID, $this->xml($response)['TXNID']);
        $airtelTransaction->refresh();
        $this->assertSame(AirtelTransaction::STATUS_PROCESSING, $airtelTransaction->status);
        $this->assertSame('AIRTEL-REF-2', $airtelTransaction->trans_id);
        $transaction = $airtelTransaction->transaction()->firstOrFail();
        $this->assertSame(6000.0, (float) $transaction->amount);
        $this->assertSame(self::METER_SERIAL, $transaction->message);
        $this->assertSame('+255712345678', $transaction->sender);
        $this->assertSame(AirtelTransaction::RELATION_NAME, $transaction->original_transaction_type);
        Queue::assertPushed(ProcessPayment::class, 1);
        $this->assertXml($this->callEnquiry(), 400, 'Pending');

        $this->transactionProvider($airtelTransaction)->sendResult(true, $transaction);

        $enquiry = $this->callEnquiry();
        $this->assertXml($enquiry, 200, 'Success');
        $this->assertSame((string) $airtelTransaction->id, $this->xml($enquiry)['REF']);
    }

    public function testAFailedPaymentIsReportedAsFailed(): void {
        $this->callValidation();
        $this->callProcess();
        $airtelTransaction = AirtelTransaction::query()->firstOrFail();

        $this->transactionProvider($airtelTransaction)->sendResult(false, $airtelTransaction->transaction()->firstOrFail());

        $this->assertXml($this->callEnquiry(), 404, 'Failed');
    }

    public function testARepeatedProcessCallDoesNotProcessThePaymentTwice(): void {
        $this->callValidation();

        $this->assertXml($this->callProcess(), 200, 'Success');
        $this->assertXml($this->callProcess(), 200, 'Success');

        Queue::assertPushed(ProcessPayment::class, 1);
    }

    public function testValidationRejectsRequestsItCannotAccept(): void {
        $missingField = '<COMMAND><TYPE>C2B</TYPE><REFERENCE1>'.self::AIRTEL_TRANSACTION_ID.'</REFERENCE1></COMMAND>';
        $rejectedRequests = [
            'unknown meter' => [$this->validationBody(meterSerial: '00000000000'), 'Meter not found with serial number you entered'],
            'amount below the minimum purchase amount' => [$this->validationBody(amount: '500'), null],
            'missing field' => [$missingField, 'The CUSTOMERMSISDN field is required.'],
            'body that is not XML' => ['not xml', 'Request body is not valid XML'],
        ];

        foreach ($rejectedRequests as $case => [$body, $message]) {
            $answer = $this->xml($this->callAirtel('validation', $body));

            $this->assertSame('400', $answer['STATUS'], $case);
            if ($message !== null) {
                $this->assertSame($message, $answer['MESSAGE'], $case);
            }
        }
        $this->assertSame(0, AirtelTransaction::query()->count());
        $this->assertSame(0, Transaction::query()->count());
    }

    public function testValidationRejectsATransactionIdThatWasAlreadyUsed(): void {
        $this->callValidation();

        $this->assertXml($this->callValidation(), 400, 'Duplicate Reference1');
        $this->assertSame(1, AirtelTransaction::query()->count());
    }

    public function testProcessAndEnquiryRejectATransactionThatWasNotValidated(): void {
        $this->assertXml($this->callProcess(), 400, 'Transaction not found');
        $this->assertXml($this->callEnquiry(), 404, 'Transaction not found');
        Queue::assertNotPushed(ProcessPayment::class);
    }

    public function testACallWithATokenThatCannotBeAcceptedIsRejected(): void {
        $rejectedTokens = [
            'signed with another secret' => [$this->token(self::AIRTEL_TRANSACTION_ID, secret: 'another-secret'), 401, 'Signature verification failed'],
            'expired' => [$this->token(self::AIRTEL_TRANSACTION_ID, expiresAt: now()->subMinute()->timestamp), 401, 'Token has expired'],
            'not signed with HS512' => [$this->token(self::AIRTEL_TRANSACTION_ID, algorithm: 'none'), 401, 'Invalid Token'],
            'not a token' => ['not-a-token', 401, 'Invalid Token'],
            'issued for another transaction' => [$this->token('ANOTHER.TRANSACTION'), 400, 'Access token contains wrong reference1'],
        ];

        foreach ($rejectedTokens as [$token, $status, $message]) {
            $this->assertXml($this->callAirtel('validation', $this->validationBody(), $token), $status, $message);
        }
        $this->assertXml(
            $this->call('POST', $this->url('validation'), content: $this->validationBody()),
            401,
            'Invalid Token'
        );
        $this->assertXml(
            $this->callAirtel('enquiry', '<COMMAND><TXNID>'.self::AIRTEL_TRANSACTION_ID.'</TXNID></COMMAND>', $this->token('ANOTHER.TRANSACTION')),
            400,
            'Access token contains wrong txnId'
        );
        $this->assertSame(0, AirtelTransaction::query()->count());

        AirtelCredential::query()->update(['jwt_secret' => null]);

        $this->assertXml($this->callValidation(), 401, 'Invalid Token');
    }

    public function testCredentialsAreStoredWithAnEncryptedSecret(): void {
        $response = $this->actingAs(UserFactory::new()->create())
            ->putJson('/api/airtel-payment-provider/credential', ['jwt_secret' => 'new-secret', 'phone_prefix' => '+256']);

        $response->assertOk();
        $response->assertJsonPath('data.phone_prefix', '+256');
        $this->assertNotSame('new-secret', AirtelCredential::query()->firstOrFail()->jwt_secret);
        $this->assertSame('new-secret', $this->app->make(AirtelCredentialService::class)->getCredentials()->jwt_secret);
    }

    public function testCredentialsWithAnInvalidPhonePrefixAreRejected(): void {
        $user = UserFactory::new()->create();

        foreach (['255', '+', '+25571', '+2a5', '+999', ''] as $phonePrefix) {
            $response = $this->actingAs($user)
                ->putJson('/api/airtel-payment-provider/credential', ['jwt_secret' => 'new-secret', 'phone_prefix' => $phonePrefix]);

            $response->assertStatus(422);
            $response->assertJsonValidationErrors('phone_prefix');
        }
    }

    private function callValidation(
        string $meterSerial = self::METER_SERIAL,
        string $amount = '6000',
        string $transactionId = self::AIRTEL_TRANSACTION_ID,
    ): TestResponse {
        return $this->callAirtel('validation', $this->validationBody($meterSerial, $amount, $transactionId), $this->token($transactionId));
    }

    private function callProcess(): TestResponse {
        return $this->callAirtel(
            'process',
            '<COMMAND><TYPE>C2B</TYPE><REFERENCE1>'.self::AIRTEL_TRANSACTION_ID.'</REFERENCE1><REFERENCE2>AIRTEL-REF-2</REFERENCE2></COMMAND>'
        );
    }

    private function callEnquiry(): TestResponse {
        return $this->callAirtel('enquiry', '<COMMAND><TXNID>'.self::AIRTEL_TRANSACTION_ID.'</TXNID></COMMAND>');
    }

    private function callAirtel(string $step, string $body, ?string $token = null): TestResponse {
        return $this->call(
            'POST',
            $this->url($step),
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.($token ?? $this->token(self::AIRTEL_TRANSACTION_ID)), 'CONTENT_TYPE' => 'application/xml'],
            content: $body,
        );
    }

    private function url(string $step): string {
        return "/api/airtel-payment-provider/callback/{$this->companyId}/{$step}";
    }

    private function validationBody(
        string $meterSerial = self::METER_SERIAL,
        string $amount = '6000',
        string $transactionId = self::AIRTEL_TRANSACTION_ID,
    ): string {
        return '<?xml version="1.0" encoding="UTF-8"?><COMMAND>'
            .'<TYPE>C2B</TYPE>'
            .'<CUSTOMERMSISDN>712345678</CUSTOMERMSISDN>'
            .'<MERCHANTMSISDN>689000111</MERCHANTMSISDN>'
            ."<AMOUNT>{$amount}</AMOUNT>"
            ."<REFERENCE>{$meterSerial}</REFERENCE>"
            ."<REFERENCE1>{$transactionId}</REFERENCE1>"
            .'</COMMAND>';
    }

    private function token(
        string $transactionId,
        string $secret = self::SECRET,
        ?int $expiresAt = null,
        string $algorithm = 'HS512',
    ): string {
        $encode = static fn (array $data): string => rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
        $signedPart = $encode(['typ' => 'JWT', 'alg' => $algorithm]).'.'.$encode([
            'exp' => $expiresAt ?? now()->addMinutes(5)->timestamp,
            'payload' => ['txnId' => $transactionId],
        ]);
        $signature = rtrim(strtr(base64_encode(hash_hmac('sha512', $signedPart, $secret, true)), '+/', '-_'), '=');

        return $signedPart.'.'.$signature;
    }

    /**
     * @return array<string, string>
     */
    private function xml(TestResponse $response): array {
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $command = simplexml_load_string((string) $response->getContent());
        $this->assertNotFalse($command, 'The answer must be XML.');
        $this->assertSame('COMMAND', $command->getName());

        return array_map(strval(...), (array) $command);
    }

    private function assertXml(TestResponse $response, int $status, string $message): void {
        $command = $this->xml($response);

        $this->assertSame($message, $command['MESSAGE']);
        $this->assertSame((string) $status, $command['STATUS']);
    }

    private function transactionProvider(AirtelTransaction $airtelTransaction): AirtelTransactionProvider {
        $provider = TransactionAdapter::getTransaction($airtelTransaction);
        $this->assertInstanceOf(AirtelTransactionProvider::class, $provider);

        return $provider;
    }

    private function seedMeter(float $tariffPrice, float $minimumPurchaseAmount): void {
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => ManufacturerFactory::new()->create(['type' => 'meter'])->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => $tariffPrice, 'minimum_purchase_amount' => $minimumPurchaseAmount])->id,
            'serial_number' => self::METER_SERIAL,
        ]);
        $person = PersonFactory::new()->create();
        DeviceFactory::new()->create([
            'person_id' => $person->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);
        $address = Address::query()->make(['phone' => '+255700000001', 'is_primary' => 1]);
        $address->owner()->associate($person)->save();
    }
}
