<?php

namespace Tests\Feature;

use App\Models\MainSettings;
use App\Models\ProtectedPagePasswordResetToken;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ProtectedPagePasswordResetTest extends TestCase {
    use RefreshDatabase;
    use WithFaker;

    private User $user;
    private MainSettings $mainSettings;

    protected function setUp(): void {
        parent::setUp();

        // Create test user
        $this->user = UserFactory::new()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Create main settings with a protected page password
        $this->mainSettings = MainSettings::create([
            'company_name' => 'Test Company',
            'site_title' => 'Test Site',
            'currency' => 'USD',
            'country' => 'US',
            'language' => 'en',
            'vat_energy' => 0.0,
            'vat_appliance' => 0.0,
            'protected_page_password' => Crypt::encrypt('oldpassword'),
        ]);
    }

    /** @test */
    public function userCanRequestPppResetEmail(): void {
        $response = $this->post('/api/protected-page-password/reset', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'message' => 'If the email exists in our system, a reset link has been sent.',
                'status_code' => 200,
            ],
        ]);

        // Check that a reset token was created
        $this->assertDatabaseHas('protected_page_password_reset_tokens', [
            'email' => $this->user->email,
        ]);
    }

    /** @test */
    public function nonExistingEmailReturnsSuccessMessage(): void {
        $response = $this->post('/api/protected-page-password/reset', [
            'email' => 'nonexisting@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'message' => 'If the email exists in our system, a reset link has been sent.',
                'status_code' => 200,
            ],
        ]);

        // No reset token should be created for non-existing email
        $this->assertDatabaseMissing('protected_page_password_reset_tokens', [
            'email' => 'nonexisting@example.com',
        ]);
    }

    /** @test */
    public function canValidateResetToken(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        $response = $this->get("/api/protected-page-password/validate/{$resetToken->token}");

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'valid' => true,
                'email' => $this->user->email,
                'status_code' => 200,
            ],
        ]);
    }

    /** @test */
    public function invalidTokenReturnsError(): void {
        $response = $this->get('/api/protected-page-password/validate/invalid-token');

        $response->assertStatus(400);
        $response->assertJson([
            'data' => [
                'valid' => false,
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ]);
    }

    /** @test */
    public function expiredTokenReturnsError(): void {
        // Create an expired token
        $resetToken = ProtectedPagePasswordResetToken::create([
            'email' => $this->user->email,
            'token' => 'expired-token',
            'expires_at' => now()->subHour(), // Expired 1 hour ago
        ]);

        $response = $this->get("/api/protected-page-password/validate/{$resetToken->token}");

        $response->assertStatus(400);
        $response->assertJson([
            'data' => [
                'valid' => false,
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ]);
    }

    /** @test */
    public function canResetPasswordWithValidToken(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);
        $oldPassword = $this->mainSettings->protected_page_password;

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'message' => 'Protected Pages Password has been reset successfully.',
                'status_code' => 200,
            ],
        ]);

        // Check that the password was updated
        $this->mainSettings->refresh();
        $this->assertNotEquals($oldPassword, $this->mainSettings->protected_page_password);
        $this->assertEquals('newpassword123', Crypt::decrypt($this->mainSettings->protected_page_password));

        // Check that the token was deleted
        $this->assertDatabaseMissing('protected_page_password_reset_tokens', [
            'token' => $resetToken->token,
        ]);
    }

    /** @test */
    public function resetPasswordWithInvalidTokenFails(): void {
        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'data' => [
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ]);
    }

    /** @test */
    public function resetPasswordValidationRequiresMatchingPasswords(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function resetPasswordValidationRequiresMinimumLength(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422); // Validation error
    }

    /** @test */
    public function resetPasswordRequiresAllFields(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        // Test missing token
        $response = $this->post('/api/protected-page-password/confirm', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertStatus(422);

        // Test missing password
        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertStatus(422);

        // Test missing password confirmation
        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'newpassword123',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function resetRequestRequiresValidEmail(): void {
        // Test missing email
        $response = $this->post('/api/protected-page-password/reset', []);
        $response->assertStatus(422);

        // Test invalid email format
        $response = $this->post('/api/protected-page-password/reset', [
            'email' => 'invalid-email',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function resetTokenExpiresAfterOneHour(): void {
        // Create a token that expires in 1 hour
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        // Verify token is valid initially
        $response = $this->get("/api/protected-page-password/validate/{$resetToken->token}");
        $response->assertStatus(200);

        // Travel forward in time by 1 hour and 1 minute
        $this->travel(61)->minutes();

        // Verify token is now expired
        $response = $this->get("/api/protected-page-password/validate/{$resetToken->token}");
        $response->assertStatus(400);
    }

    /** @test */
    public function resetTokenCanOnlyBeUsedOnce(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        // Use the token successfully
        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertStatus(200);

        // Try to use the same token again - should fail
        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'anotherpassword123',
            'password_confirmation' => 'anotherpassword123',
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function multipleResetTokensForSameEmailAreInvalidated(): void {
        // Create first reset token
        $firstToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        // Create second reset token (should invalidate the first)
        $secondToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        // First token should be invalid
        $response = $this->get("/api/protected-page-password/validate/{$firstToken->token}");
        $response->assertStatus(400);

        // Second token should be valid
        $response = $this->get("/api/protected-page-password/validate/{$secondToken->token}");
        $response->assertStatus(200);

        // Only one token should exist in database
        $this->assertDatabaseCount('protected_page_password_reset_tokens', 1);
        $this->assertDatabaseHas('protected_page_password_reset_tokens', [
            'token' => $secondToken->token,
        ]);
    }

    /** @test */
    public function resetPasswordWithExpiredTokenFails(): void {
        // Create an expired token
        $resetToken = ProtectedPagePasswordResetToken::create([
            'email' => $this->user->email,
            'token' => 'expired-token',
            'expires_at' => now()->subHour(),
        ]);

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'data' => [
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ]);
    }

    /** @test */
    public function resetPasswordWorksWithMinimumValidPassword(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => '12345', // Minimum 5 characters
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'message' => 'Protected Pages Password has been reset successfully.',
                'status_code' => 200,
            ],
        ]);
    }

    /** @test */
    public function resetPasswordWorksWithLongPassword(): void {
        // Create a reset token
        $resetToken = ProtectedPagePasswordResetToken::generateToken($this->user->email);
        $longPassword = str_repeat('a', 100); // 100 character password

        $response = $this->post('/api/protected-page-password/confirm', [
            'token' => $resetToken->token,
            'password' => $longPassword,
            'password_confirmation' => $longPassword,
        ]);

        $response->assertStatus(200);

        // Verify the password was actually updated
        $this->mainSettings->refresh();
        $this->assertEquals($longPassword, Crypt::decrypt($this->mainSettings->protected_page_password));
    }
}
