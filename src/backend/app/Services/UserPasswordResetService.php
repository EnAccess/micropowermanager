<?php

namespace App\Services;

use App\Exceptions\MailNotSentException;
use App\Helpers\MailHelper;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Log;

class UserPasswordResetService {
    public function __construct(
        private MailHelper $mailHelper,
        private UserService $userService,
        private PasswordResetToken $passwordResetToken,
    ) {}

    public function sendResetEmail(string $email): bool {
        $user = $this->userService->getByEmail($email);
        if (!$user) {
            return true;
        }

        $resetToken = $this->passwordResetToken->generateToken($email);

        try {
            $this->mailHelper->sendViaTemplate(
                $email,
                'Reset your password | MicroPowerManager',
                'templates.mail.user_password_reset',
                [
                    'userName' => $user->name,
                    'resetUrl' => $this->generateResetUrl($email, $resetToken->token),
                ]
            );

            return true;
        } catch (MailNotSentException $exception) {
            Log::error('Failed to send user password reset email: '.$exception->getMessage());

            return false;
        }
    }

    public function validateToken(string $token): ?string {
        $record = $this->passwordResetToken->newQuery()->where('token', $token)->first();

        return $record?->email;
    }

    public function resetPassword(string $token, string $password): bool {
        $record = $this->passwordResetToken->newQuery()->where('token', $token)->first();
        if (!$record) {
            return false;
        }

        $user = $this->userService->getByEmail($record->email);
        if (!$user) {
            return false;
        }

        $user->update(['password' => $password]);

        $this->passwordResetToken->newQuery()->where('email', $record->email)->delete();

        return true;
    }

    private function generateResetUrl(string $email, string $rawToken): string {
        $baseUrl = config('app.frontend_url');
        $compositeToken = $this->encodeCompositeToken($email, $rawToken);

        return $baseUrl.'/#/reset-password?token='.$compositeToken;
    }

    /**
     * Create opaque token containing email and raw token (base64url-encoded JSON).
     */
    public function encodeCompositeToken(string $email, string $rawToken): string {
        $payload = json_encode(['e' => $email, 't' => $rawToken]);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * Decode composite token to [email, rawToken].
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function decodeCompositeToken(string $compositeToken): array {
        $padded = strtr($compositeToken, '-_', '+/');
        $padLen = strlen($padded) % 4;
        if ($padLen > 0) {
            $padded .= str_repeat('=', 4 - $padLen);
        }
        $json = base64_decode($padded, true);
        if ($json === false) {
            return [null, null];
        }
        $data = json_decode($json, true);
        $email = is_array($data) && array_key_exists('e', $data) ? $data['e'] : null;
        $token = is_array($data) && array_key_exists('t', $data) ? $data['t'] : null;

        return [$email, $token];
    }
}
