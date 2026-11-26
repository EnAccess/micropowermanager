<?php

namespace App\Plugins\ChintMeter\Modules\Api;

use App\Plugins\ChintMeter\Exceptions\ChintApiResponseException;
use App\Plugins\ChintMeter\Models\ChintCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class ChintSoap {
    private const string SERVICE_NAMESPACE = 'http://jianpanbiaoEnglish/PrepaidWebService';
    private const string SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const int CONNECT_TIMEOUT_SECONDS = 10;
    private const int TIMEOUT_SECONDS = 30;

    private const array REASONS = [
        '0' => 'Other reason',
        '1' => 'No permission to access the service',
        '2' => 'Customer id does not exist',
        '3' => 'Customer status anomaly',
        '4' => 'Customer meter anomaly',
        '5' => 'SGC is disabled',
        '6' => 'Tariff anomaly',
        '7' => 'Tax anomaly',
        '8' => 'Customer meter key anomaly',
        '9' => 'Customer has been locked',
        '10' => 'Out of limit times power purchase one day',
        '11' => 'The prepaid amount exceeds maximum limit',
        '12' => 'The prepaid amount lower than minimum limit',
        '13' => 'TID already exists',
        '14' => 'TID does not exist',
        '15' => 'TID already cancels',
        '16' => 'Not previous TID',
        '17' => 'Station credit is not enough',
        '18' => 'Function transfer parameters error',
    ];

    public function __construct(
        private Client $client,
    ) {}

    /**
     * @return array{rechargeToken: string, energy: float, feeAmount: float, changeAmount: float}
     */
    public function transaction(ChintCredential $credentials, string $customerId, float $amount): array {
        $transactionId = (string) microtime(true);
        $formattedAmount = number_format($amount, 4, '.', '');

        $result = $this->call($credentials, 'Transaction', [
            'TID' => $transactionId,
            'CustomerID' => $customerId,
            'Amount' => $formattedAmount,
            'ParaSignature' => $this->getParaSignature($transactionId.';'.$customerId.';'.$formattedAmount.';'),
        ]);

        $rechargeToken = trim((string) $result->RechargeToken);

        if ($rechargeToken === '') {
            throw new ChintApiResponseException('Chint accepted the purchase but returned no token.');
        }

        return [
            'rechargeToken' => $rechargeToken,
            'energy' => (float) $result->Energy,
            'feeAmount' => (float) $result->FeeAmount,
            'changeAmount' => (float) $result->ChangeAmount,
        ];
    }

    /**
     * @return array{customerName: string, meterNo: string, minPaymentAmount: float, maxPaymentAmount: float, tariffPrice: float}
     */
    public function validation(ChintCredential $credentials, string $customerId): array {
        $result = $this->call($credentials, 'Validation', [
            'CustomerID' => $customerId,
            'ParaSignature' => $this->getParaSignature($customerId.';'),
        ]);

        return [
            'customerName' => (string) $result->CustomerName,
            'meterNo' => (string) $result->MeterNo,
            'minPaymentAmount' => (float) $result->MinPaymentAmount,
            'maxPaymentAmount' => (float) $result->MaxPaymentAmount,
            'tariffPrice' => (float) $result->TariffPrice,
        ];
    }

    /**
     * @param array<string, string> $parameters
     */
    private function call(ChintCredential $credentials, string $operation, array $parameters): \SimpleXMLElement {
        $url = $credentials->api_url;

        try {
            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'text/xml; charset=utf-8'],
                'body' => $this->buildEnvelope($credentials, $operation, $parameters),
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
                'timeout' => self::TIMEOUT_SECONDS,
                'http_errors' => false,
                // Chint serves the API from a bare IP address, so its certificate cannot be verified.
                'verify' => false,
            ]);
        } catch (GuzzleException $exception) {
            Log::critical('Chint API request failed', ['url' => $url, 'message' => $exception->getMessage()]);
            throw new ChintApiResponseException("Could not reach Chint at {$url}: {$exception->getMessage()}", $exception->getCode(), previous: $exception);
        }

        $result = $this->extractResult((string) $response->getBody(), $operation);

        if (!$result instanceof \SimpleXMLElement) {
            throw new ChintApiResponseException("Chint returned an unexpected response (HTTP {$response->getStatusCode()}). Check the API URL.");
        }

        if (strtolower(trim((string) $result->Result)) !== 'true') {
            $reasonCode = trim((string) $result->Reason);
            $reason = self::REASONS[$reasonCode] ?? 'no reason given';
            throw new ChintApiResponseException("Chint rejected the request: {$reason} (code {$reasonCode})");
        }

        return $result;
    }

    private function extractResult(string $body, string $operation): ?\SimpleXMLElement {
        if (trim($body) === '') {
            return null;
        }

        $previousSetting = libxml_use_internal_errors(true);
        $envelope = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        if ($envelope === false) {
            return null;
        }

        $result = $envelope->children(self::SOAP_NAMESPACE)->Body
            ->children()?->{$operation.'Response'}->{$operation.'Result'};

        return $result instanceof \SimpleXMLElement && $result->count() > 0 ? $result : null;
    }

    /**
     * @param array<string, string> $parameters
     */
    private function buildEnvelope(ChintCredential $credentials, string $operation, array $parameters): string {
        $envelope = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="'.self::SOAP_NAMESPACE.'"></soap:Envelope>');

        $header = $envelope->addChild('soap:Header')->addChild('PrepaidSoapHeader', null, self::SERVICE_NAMESPACE);
        $header->addChild('UserName', (string) $credentials->user_name);
        $header->addChild('PassWord', $this->getPasswordSignature((string) $credentials->user_password));

        $request = $envelope->addChild('soap:Body')->addChild($operation, null, self::SERVICE_NAMESPACE);
        foreach ($parameters as $name => $value) {
            $request->addChild($name, $value);
        }

        return (string) $envelope->asXML();
    }

    private function getPasswordSignature(#[\SensitiveParameter] string $password): string {
        $date = new \DateTime('now', new \DateTimeZone('UTC'))->format('Ymd');

        return strtoupper(md5($date.'Chint'.$password));
    }

    private function getParaSignature(string $param): string {
        $utc = new \DateTime('now', new \DateTimeZone('UTC'));
        $seconds = $utc->format('H') * 3600 + $utc->format('i') * 60 + $utc->format('s');
        $correctSec = floor($seconds / 40) * 40;

        return strtoupper(md5('Chint;'.$param.$correctSec.';'));
    }
}
