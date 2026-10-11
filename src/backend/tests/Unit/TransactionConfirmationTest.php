<?php

namespace Tests\Unit;

use App\Models\SmsBody;
use App\Services\SmsBodyService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\TransactionConfirmation;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class TransactionConfirmationTest extends TestCase {
    use MockeryPHPUnitIntegration;

    public function testPrepareBodyKeepsTheParserReferenceAndValuesForTheWhatsappTemplate(): void {
        $smsBodyService = \Mockery::mock(SmsBodyService::class);
        $smsBodyService->shouldReceive('getSmsBodyByReference')
            ->with('TransactionConfirmationNoToken')
            ->andReturn(new SmsBody(['body' => 'Dear [name] [surname], we received [amount].']));

        $sender = new TransactionConfirmation(
            ['name' => 'Amina', 'surname' => 'Juma', 'amount' => 5000, 'phone' => '+255712345678'],
            $smsBodyService,
            new SmsConfigs()->bodyParsersPath,
        );
        $sender->prepareBody();

        $this->assertSame('Dear Amina Juma, we received 5000.', $sender->body);
        $this->assertSame('TransactionConfirmationNoToken', $sender->bodyParserReference);
        $this->assertSame(['name' => 'Amina', 'surname' => 'Juma', 'amount' => '5000'], $sender->bodyParserValues);
    }
}
