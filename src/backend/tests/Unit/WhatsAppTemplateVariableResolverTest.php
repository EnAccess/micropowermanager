<?php

namespace Tests\Unit;

use App\Plugins\WhatsApp\Services\WhatsAppTemplateVariableResolver;
use Tests\TestCase;

class WhatsAppTemplateVariableResolverTest extends TestCase {
    public function testItReplacesAllowedVariables(): void {
        $resolver = new WhatsAppTemplateVariableResolver();
        $resolved = $resolver->resolve(
            'Hello {{customer_name}}, token {{ token }} amount {{payment_amount}}',
            [
                'customer_name' => 'Amara Banda',
                'token' => '123456789012',
                'payment_amount' => '500.00',
            ],
        );

        $this->assertSame('Hello Amara Banda, token 123456789012 amount 500.00', $resolved);
    }

    public function testItDoesNotExposeNonAllowListedVariables(): void {
        $resolver = new WhatsAppTemplateVariableResolver();
        $resolved = $resolver->resolve('Secret: {{api_access_token}}', [
            'api_access_token' => 'super-secret',
        ]);

        $this->assertSame('Secret: {{api_access_token}}', $resolved);
    }

    public function testItRendersMissingVariablesAsEmptyString(): void {
        $resolver = new WhatsAppTemplateVariableResolver();

        $this->assertSame('Token: ', $resolver->resolve('Token: {{token}}', []));
    }
}
