<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageComposer;
use Illuminate\Support\Carbon;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageComposerTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string BODY = 'Dear Amina Juma, we received your payment for meter 47000268748. Token: 1234-5678, energy 5.000 kWh.';
    private const array VALUES = [
        'name' => "Amina\n",
        'surname' => "  Juma \t Said ",
        'token' => '1234-5678',
        'energy' => '5.000',
        'meter_serial' => '',
    ];

    protected function setUp(): void {
        parent::setUp();

        WhatsappTemplate::query()->create([
            'body_parser' => 'TokenConfirmationMeter',
            'template_name' => 'mpm_payment_receipt_meter',
            'language_code' => 'en',
            'parameters' => ['name', 'surname', 'meter_serial', 'token', 'energy'],
        ]);
    }

    public function testInsideTheWindowItSendsTheSmsBodyAsFreeText(): void {
        $message = $this->composer()->compose(self::BODY, 'TokenConfirmationMeter', self::VALUES, $this->contact(Carbon::now()->subHours(23)));

        $this->assertFalse($message->isTemplate());
        $this->assertSame('+255712345678', $message->to);
        $this->assertSame(self::BODY, $message->body);
    }

    public function testOutsideTheWindowItSendsTheTemplateWithOrderedSanitisedParameters(): void {
        $message = $this->composer()->compose(self::BODY, 'TokenConfirmationMeter', self::VALUES, $this->contact(Carbon::now()->subMinutes(23 * 60 + 55)));

        $this->assertTrue($message->isTemplate());
        $this->assertSame('mpm_payment_receipt_meter', $message->templateName);
        $this->assertSame('en', $message->templateLanguage);
        $this->assertSame(['Amina', 'Juma Said', '-', '1234-5678', '5.000'], $message->templateParameters);
        $this->assertSame(self::BODY, $message->body);
    }

    public function testACustomerWhoNeverWroteGetsTheTemplate(): void {
        $message = $this->composer()->compose(self::BODY, 'TokenConfirmationMeter', self::VALUES, $this->contact(null));

        $this->assertTrue($message->isTemplate());
    }

    public function testOutsideTheWindowWithoutAMappingItThrows(): void {
        $this->expectException(WhatsappTemplateNotConfiguredException::class);
        $this->expectExceptionMessage('No active WhatsApp template is mapped to ApplianceRateReminder');

        $this->composer()->compose('Reminder', 'ApplianceRateReminder', [], $this->contact(null));
    }

    public function testAnInactiveMappingIsNotUsed(): void {
        WhatsappTemplate::query()->update(['is_active' => false]);

        $this->expectException(WhatsappTemplateNotConfiguredException::class);

        $this->composer()->compose(self::BODY, 'TokenConfirmationMeter', self::VALUES, $this->contact(null));
    }

    public function testOutsideTheWindowWithAMissingVariableItThrows(): void {
        $values = self::VALUES;
        unset($values['token']);

        $this->expectException(WhatsappTemplateNotConfiguredException::class);
        $this->expectExceptionMessage('expects variable "token"');

        $this->composer()->compose(self::BODY, 'TokenConfirmationMeter', $values, $this->contact(null));
    }

    public function testOutsideTheWindowAMessageWithoutABodyParserThrows(): void {
        $this->expectException(WhatsappTemplateNotConfiguredException::class);

        $this->composer()->compose('Manual message', null, [], $this->contact(null));
    }

    private function composer(): WhatsappMessageComposer {
        return resolve(WhatsappMessageComposer::class);
    }

    private function contact(?Carbon $lastInboundAt): WhatsappContact {
        return WhatsappContact::query()->create([
            'phone' => '+255712345678',
            'wa_id' => '255712345678',
            'opt_in_status' => WhatsappContact::OPT_IN_STATUS_OPTED_IN,
            'last_inbound_at' => $lastInboundAt,
        ]);
    }
}
