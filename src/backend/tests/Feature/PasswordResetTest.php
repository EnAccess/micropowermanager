<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\TestCompany;

class PasswordResetTest extends TestCase {
    public function testWebForgotPasswordDoesNotRevealWhetherTheEmailExists(): void {
        // The web reset endpoint is excluded from the tenant-resolution middleware and resolves the
        // company itself, returning the same generic message whether or not the email exists.
        $response = $this->postJson('/api/users/password', [
            'email' => 'no-such-user@example.test',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.message', 'If the email exists, a reset link has been sent.');
    }

    /**
     * Documents the CURRENT agent reset-password behaviour, which is intentionally left as-is for now:
     * an unknown email fails in middleware with a 401 while a known agent gets a 200 (and the response
     * even carries the new password).
     * That is enumerable and inconsistent with the web flow above. Not fixed here.
     * FIXME: https://github.com/EnAccess/micropowermanager/issues/1537
     */
    public function testAgentResetPasswordUnknownEmailCurrentlyReturnsUnauthorized(): void {
        $response = $this->postJson('/api/app/reset-password', [
            'email' => 'no-such-agent@example.test',
        ]);

        $response->assertStatus(401);
    }
}
