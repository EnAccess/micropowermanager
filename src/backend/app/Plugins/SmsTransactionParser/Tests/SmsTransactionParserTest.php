<?php

namespace App\Plugins\SmsTransactionParser\Tests;

use App\Plugins\SmsTransactionParser\SmsParsing\Parsers\SmsTransactionParser;
use PHPUnit\Framework\TestCase;

class SmsTransactionParserTest extends TestCase {
    public function testParsesValidVodacomMatches(): void {
        $parser = new SmsTransactionParser('Vodacom');
        $body = 'Confirmed DBQ2J80CNQY. We have recorded a purchase transaction in the amount of 100.00MT';
        $matches = [
            'amount' => '100.00',
            'transaction_ref' => 'DBQ2J80CNQY',
            'device_serial' => '996997813',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals(100.00, $result->amount);
        $this->assertNull($result->senderPhone);
        $this->assertEquals('DBQ2J80CNQY', $result->transactionReference);
        $this->assertEquals('996997813', $result->deviceSerial);
        $this->assertEquals('Vodacom', $result->providerName);
    }

    public function testParsesValidMovitelMatches(): void {
        $parser = new SmsTransactionParser('Movitel');
        $body = 'ID da transacao PP260101.0930.A12345. Transferiste 1.00MT para conta 840000001. Conteudo: 5566778899.';
        $matches = [
            'amount' => '1.00',
            'transaction_ref' => 'PP260101.0930.A12345',
            'device_serial' => '5566778899',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals(1.00, $result->amount);
        $this->assertNull($result->senderPhone);
        $this->assertEquals('PP260101.0930.A12345', $result->transactionReference);
        $this->assertEquals('5566778899', $result->deviceSerial);
        $this->assertEquals('Movitel', $result->providerName);
    }

    public function testParsesCustomProviderName(): void {
        $parser = new SmsTransactionParser('MyCustomProvider');
        $body = 'Payment received REF123 amount 500.00 for meter ABC999';
        $matches = [
            'amount' => '500.00',
            'transaction_ref' => 'REF123',
            'device_serial' => 'ABC999',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals(500.00, $result->amount);
        $this->assertEquals('REF123', $result->transactionReference);
        $this->assertEquals('ABC999', $result->deviceSerial);
        $this->assertEquals('MyCustomProvider', $result->providerName);
    }

    public function testParsesWithOptionalSenderPhone(): void {
        $parser = new SmsTransactionParser('Vodacom');
        $body = 'Confirmed ABC123XYZ. Purchase of 5,000.00 MT, reference SN12345.';
        $matches = [
            'amount' => '5,000.00',
            'sender_phone' => '258841234567',
            'transaction_ref' => 'ABC123XYZ',
            'device_serial' => 'SN12345',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals(5000.00, $result->amount);
        $this->assertEquals('258841234567', $result->senderPhone);
    }

    public function testReturnsNullWhenRequiredFieldMissing(): void {
        $parser = new SmsTransactionParser('Vodacom');

        $result = $parser->parse('some body', [
            'amount' => '5000',
        ]);

        $this->assertNull($result);
    }
}
