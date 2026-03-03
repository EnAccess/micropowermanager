<?php

namespace Tests\Unit\SmsTransactionParser;

use App\Plugins\SmsTransactionParser\Models\SmsParsingRule;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Plugins\SmsTransactionParser\Services\SmsTransactionService;
use App\Plugins\SmsTransactionParser\SmsParsing\TemplateToRegexConverter;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsTransactionServiceTest extends TestCase {
    private string $smsBody = 'Confirmed XYZ1A23BCDE. We have recorded a purchase transaction in the amount of 100.00MT, and the fee was 0.00MT, at the entity ACME SOLAR ENERGY LDA, with reference 991234567, on 26/02/26 at 8:29 AM. Your new M-Pesa balance is 140.00MT. If you have any questions, call 100. M-Pesa is easy!';

    protected function setUp(): void {
        parent::setUp();
        Queue::fake();

        $template = 'Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]';
        $converter = app(TemplateToRegexConverter::class);

        SmsParsingRule::query()->create([
            'provider_name' => 'vodacom_en',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => '/M-?Pesa/i',
            'enabled' => true,
        ]);
    }

    public function testProcessesIncomingSmsAndCreatesSmsTransaction(): void {
        $result = app(SmsTransactionService::class)->processIncomingSms($this->smsBody, 'M-Pesa');

        $this->assertNotNull($result);
        $this->assertInstanceOf(SmsTransaction::class, $result);
        $this->assertEquals('XYZ1A23BCDE', $result->transaction_reference);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals('991234567', $result->device_serial);
    }

    public function testDeduplicatesByTransactionReference(): void {
        $service = app(SmsTransactionService::class);

        $first = $service->processIncomingSms($this->smsBody, 'M-Pesa');
        $this->assertNotNull($first);

        $second = $service->processIncomingSms($this->smsBody, 'M-Pesa');
        $this->assertNull($second);

        $this->assertEquals(1, SmsTransaction::query()->where('transaction_reference', 'XYZ1A23BCDE')->count());
    }
}
