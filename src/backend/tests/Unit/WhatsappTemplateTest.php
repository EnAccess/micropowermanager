<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use Tests\TestCase;

class WhatsappTemplateTest extends TestCase {
    public function testANamedTemplateIsFilledByParameterName(): void {
        $template = $this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [
            ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Hello {{name}}'],
            ['type' => 'BODY', 'text' => 'Your token for meter {{meter_serial}} is {{token}}. Thank you, {{name}}.'],
        ]);

        $this->assertSame(['name', 'meter_serial', 'token'], $template->parameterNames());
        $this->assertSame(['type' => 'template', 'template' => [
            'name' => 'token_confirmation_meter',
            'language' => ['code' => 'en'],
            'components' => [
                ['type' => 'header', 'parameters' => [['type' => 'text', 'parameter_name' => 'name', 'text' => 'Amina']]],
                ['type' => 'body', 'parameters' => [
                    ['type' => 'text', 'parameter_name' => 'meter_serial', 'text' => '47000268748'],
                    ['type' => 'text', 'parameter_name' => 'token', 'text' => '1234-5678'],
                    ['type' => 'text', 'parameter_name' => 'name', 'text' => 'Amina'],
                ]],
            ],
        ]], $template->message(['name' => 'Amina', 'meter_serial' => '47000268748', 'token' => '1234-5678']));
    }

    public function testAPositionalTemplateIsFilledInNumberOrder(): void {
        $template = $this->template(WhatsappTemplate::PARAMETER_FORMAT_POSITIONAL, [
            ['type' => 'BODY', 'text' => 'Power in {{2}} is off until {{3}}. Dear {{1}}, we are sorry.'],
        ]);

        $this->assertSame(
            ['Amina', 'Kigali', '18:00'],
            array_column($template->message(['1' => 'Amina', '2' => 'Kigali', '3' => '18:00'])['template']['components'][0]['parameters'], 'text'),
        );
    }

    public function testValuesMetaWouldRejectAreCleanedUp(): void {
        $template = $this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [['type' => 'BODY', 'text' => 'Note: {{note}} Due: {{due_date}}.']]);

        $this->assertSame(
            ['line one line two', '-'],
            array_column($template->message(['note' => "line one\n\tline    two "])['template']['components'][0]['parameters'], 'text'),
        );
    }

    public function testRenderGivesTheWordsTheCustomerReads(): void {
        $template = $this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [
            ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Power notice'],
            ['type' => 'BODY', 'text' => 'Dear {{name}}, power in {{area}} is off until {{time}}.'],
        ]);

        $this->assertSame("Power notice\nDear Amina, power in Kigali is off until -.", $template->render(['name' => 'Amina', 'area' => " Kigali\n"]));
    }

    public function testMediaHeadersAndVariableButtonsCannotBeFilledWithText(): void {
        $body = ['type' => 'BODY', 'text' => 'Your token is {{token}}.'];

        $this->assertTrue($this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [$body, ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'STOP']]]])->canBeFilledWithText());
        $this->assertFalse($this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [['type' => 'HEADER', 'format' => 'IMAGE'], $body])->canBeFilledWithText());
        $this->assertFalse($this->template(WhatsappTemplate::PARAMETER_FORMAT_NAMED, [$body, ['type' => 'BUTTONS', 'buttons' => [['type' => 'URL', 'url' => 'https://example.com/{{1}}']]]])->canBeFilledWithText());
    }

    /** @param list<array<string, mixed>> $components */
    private function template(string $parameterFormat, array $components): WhatsappTemplate {
        return new WhatsappTemplate(['name' => 'token_confirmation_meter', 'language' => 'en', 'parameter_format' => $parameterFormat, 'components' => $components]);
    }
}
