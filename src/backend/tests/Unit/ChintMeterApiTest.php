<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Tariff;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\ChintMeter\Enums\TariffAppliedBy;
use App\Plugins\ChintMeter\Exceptions\ChintApiResponseException;
use App\Plugins\ChintMeter\Exceptions\TariffPriceDoesNotMatchException;
use App\Plugins\ChintMeter\Models\ChintCredential;
use App\Plugins\ChintMeter\Models\ChintTransaction;
use App\Plugins\ChintMeter\Modules\Api\ChintMeterApi;
use App\Plugins\ChintMeter\Modules\Api\ChintSoap;
use App\Plugins\ChintMeter\Services\ChintCredentialService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class ChintMeterApiTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private const string METER_SERIAL = '58000123456';

    /** @var array<int, array{request: Request, options: array<string, mixed>}> */
    private array $sentRequests = [];

    public function testAddCreditSendsThePaidAmountWhenChintAppliesTheTariff(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1000.0000'),
            $this->transactionResponse(token: '1234 5678 9012 3456 7890', energy: '10.0'),
        ]);

        $result = $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));

        $this->assertSame('1234 5678 9012 3456 7890', $result['token']);
        $this->assertSame(Token::TYPE_ENERGY, $result['token_type']);
        $this->assertSame(Token::UNIT_KWH, $result['token_unit']);
        $this->assertSame(10.0, $result['token_amount']);
        $this->assertSame('10000.0000', $this->sentTransactionField('Amount'));
        $this->assertSame(self::METER_SERIAL, $this->sentTransactionField('CustomerID'));
    }

    public function testAddCreditSendsTheEnergyPricedAtChintsTariffWhenMpmAppliesTheTariff(): void {
        $api = $this->api(TariffAppliedBy::Mpm, [
            $this->validationResponse(tariffPrice: '1000.0000'),
            $this->transactionResponse(token: '1111 2222 3333 4444 5555', energy: '7.7'),
        ]);
        $container = $this->transactionContainer(paidAmount: 10000, tariffPrice: 1304.8);

        $result = $api->addCredit($container);

        $this->assertSame('7700.0000', $this->sentTransactionField('Amount'));
        $this->assertSame(7.7, $result['token_amount']);
        $this->assertSame(7.7, $container->chargeAmount);
        $this->assertSame(Token::UNIT_KWH, $container->chargeUnit);
    }

    public function testAddCreditRejectsATariffMismatchWhenChintAppliesTheTariff(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1000.0000'),
        ]);

        try {
            $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1304.8));
            $this->fail('A tariff mismatch must stop the purchase.');
        } catch (TariffPriceDoesNotMatchException) {
            $this->assertCount(1, $this->sentRequests, 'No purchase may be sent to Chint.');
        }
    }

    public function testAddCreditAcceptsATariffThatDiffersOnlyByFloatNoise(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1304.8000'),
            $this->transactionResponse(token: '1234', energy: '7.6'),
        ]);

        $result = $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1304.8000000001));

        $this->assertSame(7.6, $result['token_amount']);
    }

    public function testAddCreditThrowsChintsReasonWhenThePurchaseIsRejected(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1000.0000'),
            $this->transactionResponse(token: '', energy: '0', result: 'false', reason: '12'),
        ]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint rejected the request: The prepaid amount lower than minimum limit (code 12)');

        $api->addCredit($this->transactionContainer(paidAmount: 100, tariffPrice: 1000));
    }

    public function testAddCreditThrowsChintsReasonWhenTheCustomerIsUnknown(): void {
        $api = $this->api(TariffAppliedBy::Mpm, [
            $this->validationResponse(tariffPrice: '0', result: 'false', reason: '2'),
        ]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint rejected the request: Customer id does not exist (code 2)');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1304.8));
    }

    public function testAddCreditThrowsWhenChintAcceptsThePurchaseWithoutAToken(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1000.0000'),
            $this->transactionResponse(token: '', energy: '10.0'),
        ]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('returned no token');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    public function testAddCreditThrowsWhenChintReturnsAnEmptyReply(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [new Response(200, [], '')]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint returned an unexpected response (HTTP 200)');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    public function testAddCreditThrowsWhenChintReturnsSomethingThatIsNotASoapReply(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [new Response(404, [], '<html><body>Not Found</body></html>')]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint returned an unexpected response (HTTP 404)');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    public function testAddCreditThrowsWhenChintReturnsASoapFault(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [new Response(500, [], <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
              <soap:Body>
                <soap:Fault><faultcode>soap:Server</faultcode><faultstring>Server was unable to process request.</faultstring></soap:Fault>
              </soap:Body>
            </soap:Envelope>
            XML)]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint returned an unexpected response (HTTP 500)');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    public function testAddCreditThrowsWhenChintDoesNotAnswerInTime(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            new ConnectException('Operation timed out', new Request('POST', 'https://chint.test/PrepaidWebService.asmx')),
        ]);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Could not reach Chint');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    public function testEveryRequestHasATimeout(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [
            $this->validationResponse(tariffPrice: '1000.0000'),
            $this->transactionResponse(token: '1234', energy: '10.0'),
        ]);

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));

        foreach ($this->sentRequests as $sent) {
            $this->assertGreaterThan(0, $sent['options']['timeout']);
            $this->assertGreaterThan(0, $sent['options']['connect_timeout']);
        }
    }

    public function testAddCreditThrowsWhenCredentialsAreMissing(): void {
        $api = $this->api(TariffAppliedBy::ChintPlatform, [], userName: null);

        $this->expectException(ChintApiResponseException::class);
        $this->expectExceptionMessage('Chint credentials are not configured.');

        $api->addCredit($this->transactionContainer(paidAmount: 10000, tariffPrice: 1000));
    }

    /**
     * @param array<int, Response|\Throwable> $chintReplies
     */
    private function api(TariffAppliedBy $tariffAppliedBy, array $chintReplies, ?string $userName = 'operator'): ChintMeterApi {
        $this->sentRequests = [];
        $handlerStack = HandlerStack::create(new MockHandler($chintReplies));
        $handlerStack->push(Middleware::history($this->sentRequests));

        $credentials = new ChintCredential();
        $credentials->api_url = 'https://chint.test/PrepaidWebService.asmx';
        $credentials->user_name = $userName;
        $credentials->user_password = 'secret';
        $credentials->tariff_applied_by = $tariffAppliedBy;

        /** @var ChintCredentialService&MockInterface $credentialService */
        $credentialService = \Mockery::mock(ChintCredentialService::class);
        $credentialService->shouldReceive('getCredentials')->andReturn($credentials);

        return new ChintMeterApi(
            $credentialService,
            $this->mockTransactionRecording(),
            new ChintSoap(new Client(['handler' => $handlerStack])),
        );
    }

    private function mockTransactionRecording(): ChintTransaction {
        $manufacturerTransaction = new \stdClass();
        $manufacturerTransaction->id = 1;

        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('create')->with([])->andReturn($manufacturerTransaction);

        /** @var ChintTransaction&MockInterface $chintTransaction */
        $chintTransaction = \Mockery::mock(ChintTransaction::class);
        $chintTransaction->shouldReceive('newQuery')->andReturn($builder);

        return $chintTransaction;
    }

    private function transactionContainer(float $paidAmount, float $tariffPrice): TransactionDataContainer {
        $meter = new Meter();
        $meter->serial_number = self::METER_SERIAL;

        $device = new Device();
        $device->setRelation('device', $meter);

        $tariff = new Tariff(['name' => 'Household', 'price' => $tariffPrice]);
        $tariff->setRelation('pricingComponent', new Collection());

        $originalTransaction = \Mockery::mock();
        $originalTransaction->shouldReceive('update')->andReturnNull();

        $morphTo = \Mockery::mock(MorphTo::class);
        $morphTo->shouldReceive('first')->andReturn($originalTransaction);

        $transaction = \Mockery::mock(Transaction::class);
        $transaction->shouldReceive('originalTransaction')->andReturn($morphTo);

        $container = new TransactionDataContainer();
        $container->device = $device;
        $container->tariff = $tariff;
        $container->transaction = $transaction;
        $container->amount = $paidAmount;
        $container->chargeAmount = 0;

        return $container;
    }

    private function sentTransactionField(string $field): string {
        $body = (string) end($this->sentRequests)['request']->getBody();
        $envelope = simplexml_load_string($body);
        $this->assertNotFalse($envelope);

        return (string) $envelope->children('http://schemas.xmlsoap.org/soap/envelope/')->Body
            ->children()->Transaction->{$field};
    }

    private function validationResponse(string $tariffPrice, string $result = 'true', string $reason = ''): Response {
        return $this->soapResponse('Validation', <<<XML
            <Signature>ABC</Signature>
            <Result>{$result}</Result>
            <Reason>{$reason}</Reason>
            <CustomerName>Jane Doe</CustomerName>
            <MeterNo>58000123456</MeterNo>
            <LeftPayTimes>10</LeftPayTimes>
            <ChangeAmount>0</ChangeAmount>
            <MinPaymentAmount>500</MinPaymentAmount>
            <MaxPaymentAmount>1000000</MaxPaymentAmount>
            <TariffPrice>{$tariffPrice}</TariffPrice>
            XML);
    }

    private function transactionResponse(string $token, string $energy, string $result = 'true', string $reason = ''): Response {
        return $this->soapResponse('Transaction', <<<XML
            <Signature>ABC</Signature>
            <Result>{$result}</Result>
            <Reason>{$reason}</Reason>
            <Key1Token></Key1Token>
            <Key2Token></Key2Token>
            <RechargeToken>{$token}</RechargeToken>
            <Energy>{$energy}</Energy>
            <FeeAmount>0</FeeAmount>
            <ChangeAmount>0</ChangeAmount>
            XML);
    }

    private function soapResponse(string $operation, string $fields): Response {
        return new Response(200, ['Content-Type' => 'text/xml; charset=utf-8'], <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
              <soap:Body>
                <{$operation}Response xmlns="http://jianpanbiaoEnglish/PrepaidWebService">
                  <{$operation}Result>
                    {$fields}
                  </{$operation}Result>
                </{$operation}Response>
              </soap:Body>
            </soap:Envelope>
            XML);
    }
}
