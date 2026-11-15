<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Helpers\WhatsappTemplateFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsappTemplateFormatTest extends TestCase {
    private const string METER_RECEIPT = 'Dear [name] [surname], meter [meter_serial] got token [token] for [energy] kWh';
    private const string COMPANY = 'Sunrise Energy';

    public function testItNamesTheTemplateAfterTheReferenceAndItsWording(): void {
        $name = WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT, self::COMPANY);

        $this->assertMatchesRegularExpression('/^mpm_token_confirmation_meter_[0-9a-f]{8}$/', $name);
        $this->assertSame($name, WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT, self::COMPANY));
        $this->assertNotSame($name, WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT.' today', self::COMPANY));
        $this->assertNotSame($name, WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT, 'Other Energy'));
    }

    public function testItWrapsTheSmsTextWithTheCompanyNameAndKeepsItsVariableNames(): void {
        $definition = WhatsappTemplateFormat::definition('TokenConfirmationMeter', self::METER_RECEIPT, self::COMPANY);

        $this->assertSame(WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT, self::COMPANY), $definition['name']);
        $this->assertSame('en', $definition['language']);
        $this->assertSame('UTILITY', $definition['category']);
        $this->assertSame('NAMED', $definition['parameter_format']);
        $this->assertSame('Sunrise Energy: Dear {{name_surname}}, meter {{meter_serial}} got token {{token}} for {{energy}} kWh. Thank you.', $definition['components'][0]['text']);
    }

    public function testItGivesMetaARealisticSampleForEachVariable(): void {
        $definition = WhatsappTemplateFormat::definition('TokenConfirmationMeter', self::METER_RECEIPT.' [unknown_variable]', self::COMPANY);

        $this->assertSame(
            ['name_surname' => 'Amina Hassan', 'meter_serial' => '47000123456', 'token' => '1234-5678-9012-3456', 'energy' => '12.5', 'unknown_variable' => '12345'],
            array_column($definition['components'][0]['example']['body_text_named_params'], 'example', 'param_name'),
        );
    }

    #[DataProvider('untidySmsTexts')]
    public function testItTidiesTheSmsTextSoAVariableIsNeverFirstOrLast(string $smsText, string $templateText): void {
        $this->assertSame($templateText, WhatsappTemplateFormat::definition('ApplianceRateReminder', $smsText, self::COMPANY)['components'][0]['text']);
    }

    /** @return array<string, array{string, string}> */
    public static function untidySmsTexts(): array {
        return [
            'stray punctuation and double spaces' => [
                'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]',
                'Sunrise Energy: The next rate of {{appliance_type_name}} ({{remaining}}) is due on {{due_date}}. Thank you.',
            ],
            'space before a comma' => [
                'Meter: [meter] , [token]  Unit [energy] KWH. Service Charge: [amount]',
                'Sunrise Energy: Meter: {{meter}}, {{token}} Unit {{energy}} KWH. Service Charge: {{amount}}. Thank you.',
            ],
            'literal newline' => [
                'Transaction amount is [amount], \n VAT for energy : [vat_energy] . ',
                'Sunrise Energy: Transaction amount is {{amount}}, VAT for energy: {{vat_energy}}. Thank you.',
            ],
            'side-by-side variables become one parameter' => [
                'Device [device_serial] is now active for [duration] [unit].',
                'Sunrise Energy: Device {{device_serial}} is now active for {{duration_unit}}. Thank you.',
            ],
            'own closing punctuation is kept' => [
                'Is [amount] correct?',
                'Sunrise Energy: Is {{amount}} correct? Thank you.',
            ],
        ];
    }

    public function testItSendsEachParameterOnceByNameWithTheCleanJoinedValues(): void {
        $message = WhatsappTemplateFormat::message('TokenConfirmationMeter', self::METER_RECEIPT.' [name]', self::COMPANY, [
            'name' => "Amina\n",
            'surname' => "  Juma \t Said ",
            'meter_serial' => '',
            'token' => '0',
        ]);

        $this->assertSame('template', $message['type']);
        $this->assertSame(WhatsappTemplateFormat::name('TokenConfirmationMeter', self::METER_RECEIPT.' [name]', self::COMPANY), $message['template']['name']);
        $this->assertSame(['code' => 'en'], $message['template']['language']);
        $this->assertSame(
            ['name_surname' => 'Amina Juma Said', 'meter_serial' => '-', 'token' => '0', 'energy' => '-', 'name' => 'Amina'],
            array_column($message['template']['components'][0]['parameters'], 'text', 'parameter_name'),
        );
    }
}
