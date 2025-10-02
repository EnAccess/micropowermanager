<?php

namespace App\Services;

use App\Exceptions\MailNotSentException;
use App\Helpers\MailHelper;
use App\Models\ProtectedPagePasswordResetToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProtectedPagePasswordResetService {
    public function __construct(
        private MailHelper $mailHelper,
        private MainSettingsService $mainSettingsService,
        private UserService $userService,
        private ProtectedPagePasswordResetToken $protectedPagePasswordResetToken,
    ) {}

    /**
     * Send a PPP reset email to the given email address.
     */
    public function sendResetEmail(string $email): bool {
        $user = $this->userService->getByEmail($email);
        if (!$user instanceof User) {
            return true;
        }

        $resetToken = $this->protectedPagePasswordResetToken->generateToken($email);

        $mainSettings = $this->mainSettingsService->getAll()->first();
        $companyName = $mainSettings ? $mainSettings->company_name : 'MicroPowerManager';

        try {
            // Send reset email
            $this->mailHelper->sendViaTemplate(
                $email,
                'Reset Protected Pages Password | '.$companyName,
                'templates.mail.protected_page_password_reset',
                [
                    'userName' => $user->name,
                    'companyName' => $companyName,
                    'resetUrl' => $this->generateResetUrl($resetToken->token),
                    'expiresAt' => $resetToken->expires_at->format('Y-m-d H:i:s'),
                ]
            );

            return true;
        } catch (MailNotSentException $exception) {
            Log::error('Failed to send PPP reset email: '.$exception->getMessage());

            return false;
        }
    }

    /**
     * Reset the PPP using a valid token.
     */
    public function resetPassword(string $token, string $newPassword): bool {
        $resetToken = $this->protectedPagePasswordResetToken->newQuery()->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetToken) {
            return false;
        }

        $user = $this->userService->getByEmail($resetToken->email);
        if (!$user instanceof User) {
            return false;
        }

        $mainSettings = $this->mainSettingsService->getAll()->first();
        if (!$mainSettings) {
            return false;
        }

        $this->mainSettingsService->update($mainSettings, [
            'protected_page_password' => $newPassword,
        ]);

        $resetToken->delete();

        return true;
    }

    /**
     * Validate a reset token without using it.
     */
    public function validateToken(string $token): ?string {
        $resetToken = $this->protectedPagePasswordResetToken->newQuery()->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        return $resetToken ? $resetToken->email : null;
    }

    /**
     * Generate the reset URL for the given token.
     */
    private function generateResetUrl(string $token): string {
        $baseUrl = config('app.frontend_url');

        return $baseUrl.'/#/reset-protected-password?token='.$token;
    }
}
