<?php

namespace Tests\Feature;

use Tests\TestCase;

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

    public function testAgentResetPasswordDoesNotRevealWhetherTheEmailExists(): void {
        // Fixes https://github.com/EnAccess/micropowermanager/issues/1537: the agent reset
        // endpoint is now excluded from the tenant-resolution middleware and resolves the
        // company itself, returning the same generic message whether or not the email exists.
        $response = $this->postJson('/api/app/reset-password', [
            'email' => 'no-such-agent@example.test',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.message', 'If the email exists, a new password has been sent to it.');
    }
}
