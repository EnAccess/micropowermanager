<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestCompany;

class AuthLoginTest extends TestCase {
    public function testUnknownEmailIsIndistinguishableFromAWrongPassword(): void {
        // An unknown email is resolved to its company in middleware before the controller runs.
        // That lookup must not surface as a 404 — otherwise the unknown-email (404) vs
        // wrong-password (401) difference lets an attacker enumerate which emails have accounts.
        $unknownEmail = $this->postJson('/api/auth/login', [
            'email' => 'no-such-user@example.test',
            'password' => 'whatever',
        ]);

        $wrongPassword = $this->postJson('/api/auth/login', [
            'email' => TestCompany::TEST_COMPANY_ADMIN_EMAIL,
            'password' => 'definitely-not-the-password',
        ]);

        $unknownEmail->assertStatus(401);
        $wrongPassword->assertStatus(401);
        $this->assertSame($wrongPassword->getContent(), $unknownEmail->getContent());
    }

    public function testAgentLoginUnknownEmailIsIndistinguishableFromAWrongPassword(): void {
        // Same enumeration concern as web login, but for the agent app: the unknown email fails in
        // middleware while the wrong password fails in the controller — both must look identical.
        $unknownEmail = $this->postJson('/api/app/login', [
            'email' => 'no-such-agent@example.test',
            'password' => 'whatever',
        ]);

        $wrongPassword = $this->postJson('/api/app/login', [
            'email' => TestCompany::TEST_COMPANY_ADMIN_EMAIL,
            'password' => 'definitely-not-the-password',
        ]);

        $unknownEmail->assertStatus(401);
        $wrongPassword->assertStatus(401);
        $this->assertSame($wrongPassword->getContent(), $unknownEmail->getContent());
    }
}
