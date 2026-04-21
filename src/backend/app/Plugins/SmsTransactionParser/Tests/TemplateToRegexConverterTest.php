<?php

namespace App\Plugins\SmsTransactionParser\Tests;

use App\Plugins\SmsTransactionParser\SmsParsing\TemplateToRegexConverter;
use PHPUnit\Framework\TestCase;

class TemplateToRegexConverterTest extends TestCase {
    private TemplateToRegexConverter $converter;

    protected function setUp(): void {
        parent::setUp();
        $this->converter = new TemplateToRegexConverter();
    }

    public function testWildcardMatchesRealVodacomEnglishSms(): void {
        $template = 'Confirmed [transaction_ref].[*]amount of [amount]MT[*]reference [device_serial][*]';
        $regex = $this->converter->convert($template);
        $sms = 'Confirmed XYZ1A23BCDE. We have recorded a purchase transaction in the amount of 100.00MT, and the fee was 0.00MT, at the entity ACME SOLAR ENERGY LDA, with reference 991234567, on 26/02/26 at 8:29 AM. Your new M-Pesa balance is 140.00MT. If you have any questions, call 100. M-Pesa is easy!';

        $this->assertSame(1, preg_match($regex, $sms, $matches));
        $this->assertEquals('XYZ1A23BCDE', $matches['transaction_ref']);
        $this->assertEquals('100.00', $matches['amount']);
        $this->assertEquals('991234567', $matches['device_serial']);
    }

    public function testWildcardMatchesRealVodacomPortugueseSms(): void {
        $template = 'Confirmado [transaction_ref].[*]valor de [amount]MT[*]referencia [device_serial][*]';
        $regex = $this->converter->convert($template);
        $sms = 'Confirmado XYZ1A23BCDE. Registamos uma operacao de compra no valor de 100.00MT e a taxa foi de 0.00MT na entidade ACME SOLAR ENERGY LDA com referencia 991234567 aos 26/2/26 as 8:29 AM. O teu novo saldo M-Pesa e de 140.00MT. Em caso de duvida, liga 100. M-Pesa e facil!';

        $this->assertSame(1, preg_match($regex, $sms, $matches));
        $this->assertEquals('XYZ1A23BCDE', $matches['transaction_ref']);
        $this->assertEquals('100.00', $matches['amount']);
        $this->assertEquals('991234567', $matches['device_serial']);
    }

    public function testWildcardMatchesRealEmolaPortugueseSms(): void {
        $template = 'ID da transacao[*][transaction_ref].[*][amount]MT[*]Conteudo:[*][device_serial].[*]';
        $regex = $this->converter->convert($template);
        $sms = 'ID da transacao PP260101.0930.A12345. Transferiste 1.00MT para conta 840000001, nome: Joao Manuel Silva as 11:40:51 de 03/03/2026. Taxa: 0.00MT. O saldo da tua conta e 404.00MT. Conteudo: 5566778899. Em caso de duvida, liga 100. Obrigado!';

        $this->assertSame(1, preg_match($regex, $sms, $matches));
        $this->assertEquals('PP260101.0930.A12345', $matches['transaction_ref']);
        $this->assertEquals('1.00', $matches['amount']);
        $this->assertEquals('5566778899', $matches['device_serial']);
    }

    public function testWildcardMatchesRealEmolaEnglishSms(): void {
        $template = 'Transaction ID [transaction_ref].[*][amount] MT[*]Content:[*][device_serial].[*]';
        $regex = $this->converter->convert($template);
        $sms = 'Transaction ID PP260101.0930.A12345. You transferred 1.00 MT to account 840000001, name: Joao Manuel Silva, at 11:40:51 on 03/03/2026. Fee: 0.00 MT. Your account balance is 404.00 MT. Content: 5566778899. If you have any questions, call 100. Thank you!';

        $this->assertSame(1, preg_match($regex, $sms, $matches));
        $this->assertEquals('PP260101.0930.A12345', $matches['transaction_ref']);
        $this->assertEquals('1.00', $matches['amount']);
        $this->assertEquals('5566778899', $matches['device_serial']);
    }

    public function testCaseInsensitiveMatching(): void {
        $template = 'Confirmado [transaction_ref].[*]valor de [amount]MT[*]referencia [device_serial][*]';
        $regex = $this->converter->convert($template);
        $sms = 'CONFIRMADO ABC123. operacao no VALOR DE 500.00MT com REFERENCIA 12345 fim';

        $this->assertSame(1, preg_match($regex, $sms, $matches));
        $this->assertEquals('ABC123', $matches['transaction_ref']);
        $this->assertEquals('500.00', $matches['amount']);
        $this->assertEquals('12345', $matches['device_serial']);
    }

    public function testDoesNotMatchEmolaSmsWithoutConteudo(): void {
        $template = 'ID da transacao[*][transaction_ref].[*][amount]MT[*]Conteudo:[*][device_serial].[*]';
        $regex = $this->converter->convert($template);
        $sms = 'ID da Transacao: CO260101.0829.b99999. Efectuou um pagamento de 50.00 MT para Movitel,SA. A 08:29 03/03/2026. O seu saldo actual e de 405.00 MT. Obrigado!';

        $this->assertSame(0, preg_match($regex, $sms));
    }
}
