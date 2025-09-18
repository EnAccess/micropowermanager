<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProtectedPagePasswordConfirmRequest;
use App\Http\Requests\ProtectedPagePasswordResetRequest;
use App\Services\ProtectedPagePasswordResetService;
use Illuminate\Http\JsonResponse;

class ProtectedPagePasswordResetController extends Controller {
    public function __construct(
        private ProtectedPagePasswordResetService $resetService,
    ) {}

    /**
     * Send a PPP reset email.
     */
    public function sendResetEmail(ProtectedPagePasswordResetRequest $request): JsonResponse {
        $email = $request->input('email');

        $success = $this->resetService->sendResetEmail($email);

        if ($success) {
            return response()->json([
                'data' => [
                    'message' => 'If the email exists in our system, a reset link has been sent.',
                    'status_code' => 200,
                ],
            ], 200);
        }

        return response()->json([
            'data' => [
                'message' => 'Failed to send reset email. Please try again later.',
                'status_code' => 500,
            ],
        ], 500);
    }

    /**
     * Validate a reset token.
     */
    public function validateToken(string $token): JsonResponse {
        $email = $this->resetService->validateToken($token);

        if ($email) {
            return response()->json([
                'data' => [
                    'valid' => true,
                    'email' => $email,
                    'status_code' => 200,
                ],
            ], 200);
        }

        return response()->json([
            'data' => [
                'valid' => false,
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ], 400);
    }

    /**
     * Reset the PPP using a valid token.
     */
    public function resetPassword(ProtectedPagePasswordConfirmRequest $request): JsonResponse {
        $token = $request->input('token');
        $password = $request->input('password');

        $success = $this->resetService->resetPassword($token, $password);

        if ($success) {
            return response()->json([
                'data' => [
                    'message' => 'Protected Pages Password has been reset successfully.',
                    'status_code' => 200,
                ],
            ], 200);
        }

        return response()->json([
            'data' => [
                'message' => 'Invalid or expired reset token.',
                'status_code' => 400,
            ],
        ], 400);
    }
}
