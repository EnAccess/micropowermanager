<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Helpers\WhatsappTemplateFormat;
use PHPUnit\Framework\TestCase;

class WhatsappTemplateFormatTest extends TestCase {
    private const string METER_RECEIPT = 'Dear [name] [surname], meter [meter_serial] got token [token] for [energy] kWh';

    public function testItNamesTheTemplateAfterTheSmsBodyReference(): void {
        $this->assertSame('mpm_token_confirmation_meter', WhatsappTemplateFormat::name('TokenConfirmationMeter'));
    }

    public function testItKeepsTheSmsTextAndItsVariableNames(): void {
        $definition = WhatsappTemplateFormat::definition('TokenConfirmationMeter', self::METER_RECEIPT);

        $this->assertSame('mpm_token_confirmation_meter', $definition['name']);
        $this->assertSame('en', $definition['language']);
        $this->assertSame('UTILITY', $definition['category']);
        $this->assertSame('NAMED', $definition['parameter_format']);
        $this->assertSame('Dear {{name}} {{surname}}, meter {{meter_serial}} got token {{token}} for {{energy}} kWh', $definition['components'][0]['text']);
        $this->assertSame(
            ['name', 'surname', 'meter_serial', 'token', 'energy'],
            array_column($definition['components'][0]['example']['body_text_named_params'], 'param_name'),
        );
    }

    public function testItEndsABodyThatEndsWithAVariableWithAFullStop(): void {
        $definition = WhatsappTemplateFormat::definition('ApplianceRateReminder', 'Pay [remaining] by [due_date]');

        $this->assertSame('Pay {{remaining}} by {{due_date}}.', $definition['components'][0]['text']);
    }

    public function testItSendsEachVariableOnceByNameWithACleanValue(): void {
        $message = WhatsappTemplateFormat::message('TokenConfirmationMeter', self::METER_RECEIPT.' [name]', [
            'name' => "Amina\n",
            'surname' => "  Juma \t Said ",
            'meter_serial' => '',
            'token' => '0',
        ]);

        $this->assertSame('template', $message['type']);
        $this->assertSame(['code' => 'en'], $message['template']['language']);
        $this->assertSame(
            ['name' => 'Amina', 'surname' => 'Juma Said', 'meter_serial' => '-', 'token' => '0', 'energy' => '-'],
            array_column($message['template']['components'][0]['parameters'], 'text', 'parameter_name'),
        );
    }
}
