<?php

namespace Inensus\ChintMeter\Modules\Api;

use Illuminate\Support\Facades\Log;
use Inensus\ChintMeter\Exceptions\ChintApiResponseException;
use Inensus\ChintMeter\Models\ChintCredential;

class ChintSoap {
    public function __construct(
        private ChintCredential $credentials,
        private string $customerId,
        private ?float $amount,
    ) {}

    public function transaction() {
        $tid = $this->getCustomOrderID();
        $param = $tid.';'.$this->customerId.';'.number_format($this->amount, 4, '.', '').';';
        $passwordSignature = $this->getPasswordSignature();
        $paraSignature = $this->getParaSignature($param);
        $xml = $this->createTransactionRequestXml($tid, $paraSignature, $passwordSignature);
        $response = $this->sendSoapRequest($xml);

        return $this->parseTransactionResponse($response);
    }

    public function validation() {
        $param = $this->customerId.';';
        $passwordSignature = $this->getPasswordSignature();
        $paraSignature = $this->getParaSignature($param);
        $xml = $this->createValidationRequestXml($passwordSignature, $paraSignature);
        $response = $this->sendSoapRequest($xml);

        return $this->parseValidationResponse($response);
    }

    private function sendSoapRequest(string $xml) {
        $url = $this->credentials->api_url;
        $headers = [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: '.strlen($xml),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            Log::error('ChintMeterApi error: '.curl_error($ch));
            throw new ChintApiResponseException('ChintMeterApi error: '.curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }

    private function createTransactionRequestXml(string $tid, string $paraSignature, string $passwordSignature) {
        $username = $this->credentials->user_name;
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"></soap:Envelope>');

        $header = $xml->addChild('soap:Header');
        $prepaidSoapHeader = $header->addChild('PrepaidSoapHeader', null, 'http://jianpanbiaoEnglish/PrepaidWebService');
        $prepaidSoapHeader->addChild('UserName', $username);
        $prepaidSoapHeader->addChild('PassWord', $passwordSignature);

        $body = $xml->addChild('soap:Body');
        $transaction = $body->addChild('Transaction', null, 'http://jianpanbiaoEnglish/PrepaidWebService');
        $transaction->addChild('TID', $tid);
        $transaction->addChild('CustomerID', $this->customerId);
        $transaction->addChild('Amount', number_format($this->amount, 4, '.', ''));
        $transaction->addChild('ParaSignature', $paraSignature);

        return $xml->asXML();
    }

    private function createValidationRequestXml(string $passwordSignature, string $paraSignature) {
        $username = $this->credentials->user_name;
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?><soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"></soap:Envelope>');

        $header = $xml->addChild('soap:Header');
        $prepaidSoapHeader = $header->addChild('PrepaidSoapHeader', null, 'http://jianpanbiaoEnglish/PrepaidWebService');
        $prepaidSoapHeader->addChild('UserName', $username);
        $prepaidSoapHeader->addChild('PassWord', $passwordSignature);

        $body = $xml->addChild('soap:Body');
        $validation = $body->addChild('Validation', null, 'http://jianpanbiaoEnglish/PrepaidWebService');
        $validation->addChild('CustomerID', $this->customerId);
        $validation->addChild('ParaSignature', $paraSignature);

        return $xml->asXML();
    }

    private function getPasswordSignature() {
        $password = $this->credentials->user_password;
        $date = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Ymd');
        $msgText = $date.'Chint'.$password;

        return strtoupper(md5($msgText));
    }

    private function getParaSignature($param) {
        $utc = new \DateTime('now', new \DateTimeZone('UTC'));
        $seconds = $utc->format('H') * 3600 + $utc->format('i') * 60 + $utc->format('s');
        $correctSec = floor($seconds / 40) * 40;

        $msgText = 'Chint;'.$param.$correctSec.';';

        return strtoupper(md5($msgText));
    }

    private function getCustomOrderID() {
        return (string) microtime(true);
    }

    private function parseTransactionResponse($response) {
        try {
            $xml = simplexml_load_string($response);
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;
            $transactionResponse = $body->children()->TransactionResponse;
            $transactionResult = $transactionResponse->TransactionResult;

            return [
                'signature' => (string) $transactionResult->Signature,
                'result' => (string) $transactionResult->Result,
                'reason' => (string) $transactionResult->Reason,
                'key1Token' => (string) $transactionResult->Key1Token,
                'key2Token' => (string) $transactionResult->Key2Token,
                'rechargeToken' => (string) $transactionResult->RechargeToken,
                'energy' => (float) $transactionResult->Energy,
                'feeAmount' => (float) $transactionResult->FeeAmount,
                'changeAmount' => (float) $transactionResult->ChangeAmount,
            ];
        } catch (\Exception $e) {
            Log::error('ChintMeterApi error: '.$e->getMessage());
            throw new ChintApiResponseException('ChintMeterApi error: '.$e->getMessage());
        }
    }

    private function parseValidationResponse($response) {
        try {
            $xml = simplexml_load_string($response);
            $namespaces = $xml->getNamespaces(true);
            $body = $xml->children($namespaces['soap'])->Body;
            $validationResponse = $body->children()->ValidationResponse;
            $validationResult = $validationResponse->ValidationResult;

            return [
                'signature' => (string) $validationResult->Signature,
                'result' => (string) $validationResult->Result,
                'reason' => (string) $validationResult->Reason,
                'customerName' => (string) $validationResult->CustomerName,
                'meterNo' => (string) $validationResult->MeterNo,
                'leftPayTimes' => (float) $validationResult->LeftPayTimes,
                'changeAmount' => (float) $validationResult->ChangeAmount,
                'minPaymentAmount' => (float) $validationResult->MinPaymentAmount,
                'maxPaymentAmount' => (float) $validationResult->MaxPaymentAmount,
                'tariffPrice' => (float) $validationResult->TariffPrice,
            ];
        } catch (\Exception $e) {
            Log::error('ChintMeterApi error: '.$e->getMessage());
            throw new ChintApiResponseException('ChintMeterApi error: '.$e->getMessage());
        }
    }
}
