<?php

namespace Tests\Unit\SmsTransactionParser;

use App\Plugins\SmsTransactionParser\Models\SmsParsingRule;
use App\Plugins\SmsTransactionParser\SmsParsing\SmsParserFactory;
use App\Plugins\SmsTransactionParser\SmsParsing\TemplateToRegexConverter;
use Tests\TestCase;

class SmsParserFactoryTest extends TestCase {
    public function testMatchesVodacomEnglishSms(): void {
        $converter = app(TemplateToRegexConverter::class);
        $template = 'Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]';

        SmsParsingRule::query()->create([
            'provider_name' => 'Vodacom',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => '/M-?Pesa/i',
            'enabled' => true,
        ]);

        $result = app(SmsParserFactory::class)->parse(
            'Confirmed XYZ1A23BCDE. We have recorded a purchase transaction in the amount of 100.00MT, and the fee was 0.00MT, at the entity ACME SOLAR ENERGY LDA, with reference 991234567, on 26/02/26 at 8:29 AM. Your new M-Pesa balance is 140.00MT. If you have any questions, call 100. M-Pesa is easy!',
            'M-Pesa',
        );

        $this->assertNotNull($result);
        $this->assertEquals('Vodacom', $result->providerName);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals('XYZ1A23BCDE', $result->transactionReference);
        $this->assertEquals('991234567', $result->deviceSerial);
        $this->assertNull($result->senderPhone);
    }

    public function testMatchesVodacomPortugueseSms(): void {
        $converter = app(TemplateToRegexConverter::class);
        $template = 'Confirmado [transaction_ref].[*]valor de [amount]MT[*]referencia [device_serial][*]';

        SmsParsingRule::query()->create([
            'provider_name' => 'Vodacom',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => '/M-?Pesa/i',
            'enabled' => true,
        ]);

        $result = app(SmsParserFactory::class)->parse(
            'Confirmado XYZ1A23BCDE. Registamos uma operacao de compra no valor de 100.00MT e a taxa foi de 0.00MT na entidade ACME SOLAR ENERGY LDA com referencia 991234567 aos 26/2/26 as 8:29 AM. O teu novo saldo M-Pesa e de 140.00MT. Em caso de duvida, liga 100. M-Pesa e facil!',
            'M-Pesa',
        );

        $this->assertNotNull($result);
        $this->assertEquals('Vodacom', $result->providerName);
        $this->assertEquals(100.00, $result->amount);
        $this->assertEquals('XYZ1A23BCDE', $result->transactionReference);
        $this->assertEquals('991234567', $result->deviceSerial);
    }

    public function testMatchesMovitelEmolaPortugueseSms(): void {
        $converter = app(TemplateToRegexConverter::class);
        $template = 'ID da transacao[*][transaction_ref].[*][amount]MT[*]Conteudo:[*][device_serial].[*]';

        SmsParsingRule::query()->create([
            'provider_name' => 'Movitel',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => '/e-?Mola/i',
            'enabled' => true,
        ]);

        $result = app(SmsParserFactory::class)->parse(
            'ID da transacao PP260101.0930.A12345. Transferiste 1.00MT para conta 840000001, nome: Joao Manuel Silva as 11:40:51 de 03/03/2026. Taxa: 0.00MT. O saldo da tua conta e 404.00MT. Conteudo: 5566778899. Em caso de duvida, liga 100. Obrigado!',
            'eMola',
        );

        $this->assertNotNull($result);
        $this->assertEquals('Movitel', $result->providerName);
        $this->assertEquals(1.00, $result->amount);
        $this->assertEquals('PP260101.0930.A12345', $result->transactionReference);
        $this->assertEquals('5566778899', $result->deviceSerial);
    }

    public function testMatchesMovitelEmolaEnglishSms(): void {
        $converter = app(TemplateToRegexConverter::class);
        $template = 'Transaction ID [transaction_ref].[*][amount] MT[*]Content:[*][device_serial].[*]';

        SmsParsingRule::query()->create([
            'provider_name' => 'Movitel',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => '/e-?Mola/i',
            'enabled' => true,
        ]);

        $result = app(SmsParserFactory::class)->parse(
            'Transaction ID PP260101.0930.A12345. You transferred 1.00 MT to account 840000001, name: Joao Manuel Silva, at 11:40:51 on 03/03/2026. Fee: 0.00 MT. Your account balance is 404.00 MT. Content: 5566778899. If you have any questions, call 100. Thank you!',
            'eMola',
        );

        $this->assertNotNull($result);
        $this->assertEquals('Movitel', $result->providerName);
        $this->assertEquals(1.00, $result->amount);
        $this->assertEquals('PP260101.0930.A12345', $result->transactionReference);
        $this->assertEquals('5566778899', $result->deviceSerial);
    }

    public function testReturnsNullForUnrecognizedMessage(): void {
        $converter = app(TemplateToRegexConverter::class);
        $template = 'Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]';

        SmsParsingRule::query()->create([
            'provider_name' => 'Vodacom',
            'template' => $template,
            'pattern' => $converter->convert($template),
            'sender_pattern' => null,
            'enabled' => true,
        ]);

        $result = app(SmsParserFactory::class)->parse('Hello, your balance is 100 MT', 'M-Pesa');

        $this->assertNull($result);
    }
}
