<?php

namespace Tests\Unit\SmsTransactionParser;

use App\Plugins\SmsTransactionParser\SmsParsing\Parsers\VodacomTransactionParser;
use PHPUnit\Framework\TestCase;

class VodacomTransactionParserTest extends TestCase {
    public function testParsesValidRegexMatches(): void {
        $parser = new VodacomTransactionParser();
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
        $this->assertEquals('vodacom', $result->providerName);
    }

    public function testParsesWithOptionalSenderPhone(): void {
        $parser = new VodacomTransactionParser();
        $body = 'Confirmed ABC123XYZ. Purchase of 5,000.00 MT, reference SN12345.';
        $matches = [
            'amount' => '5,000.00',
            'sender_phone' => '258841234567',
            'transaction_ref' => 'ABC123XYZ',
            'device_serial' => 'SN12345',
        ];

        $result = $parser->parse($body, $matches);

        $this->assertNotNull($result);
        $this->assertEquals('258841234567', $result->senderPhone);
    }

    public function testReturnsNullWhenRequiredFieldMissing(): void {
        $parser = new VodacomTransactionParser();

        $result = $parser->parse('some body', [
            'amount' => '5000',
        ]);

        $this->assertNull($result);
    }
}
