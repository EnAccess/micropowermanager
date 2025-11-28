<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Http\Requests\UserPasswordResetConfirmRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\DatabaseProxyManagerService;
use App\Services\UserPasswordResetService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;

class UserPasswordController extends Controller {
    public function __construct(private UserService $userService, private DatabaseProxyManagerService $databaseProxyManagerService, private UserPasswordResetService $userPasswordResetService) {}

    public function forgotPassword(AdminResetPasswordRequest $request, Response $response): Response {
        $email = $request->input('email');

        try {
            $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);
            $companyId = $databaseProxy->getCompanyId();

            return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($email, $response): Response {
                if (!$this->userPasswordResetService->sendResetEmail($email)) {
                    return $response->setStatusCode(422)->setContent(
                        [
                            'data' => [
                                'message' => 'Failed to send reset email. Please try it again later.',
                                'status_code' => 409,
                            ],
                        ]
                    );
                }

                return $response->setStatusCode(200)->setContent(
                    [
                        'data' => [
                            'message' => 'If the email exists, a reset link has been sent.',
                            'status_code' => 200,
                        ],
                    ]
                );
            });
        } catch (ModelNotFoundException) {
            return $response->setStatusCode(200)->setContent(
                [
                    'data' => [
                        'message' => 'If the email exists, a reset link has been sent.',
                        'status_code' => 200,
                    ],
                ]
            );
        }
    }

    public function validateResetToken(string $token): ApiResource {
        [$email, $rawToken] = $this->userPasswordResetService->decodeCompositeToken($token);
        if (!$email || !$rawToken) {
            return new ApiResource([
                'valid' => false,
                'email' => null,
            ]);
        }

        try {
            $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);
            $companyId = $databaseProxy->getCompanyId();

            return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($rawToken): ApiResource {
                $resolvedEmail = $this->userPasswordResetService->validateToken($rawToken);

                return new ApiResource([
                    'valid' => $resolvedEmail !== null,
                    'email' => $resolvedEmail,
                ]);
            });
        } catch (ModelNotFoundException) {
            return new ApiResource([
                'valid' => false,
                'email' => null,
            ]);
        }
    }

    public function confirmReset(UserPasswordResetConfirmRequest $request): ApiResource {
        $token = $request->input('token');
        $password = $request->input('password');

        [$email, $rawToken] = $this->userPasswordResetService->decodeCompositeToken($token);
        if (!$email || !$rawToken) {
            return new ApiResource([
                'message' => 'Invalid or expired token.',
                'status_code' => 400,
            ]);
        }

        try {
            $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);
            $companyId = $databaseProxy->getCompanyId();

            return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($rawToken, $password): ApiResource {
                $success = $this->userPasswordResetService->resetPassword($rawToken, $password);

                return new ApiResource([
                    'message' => $success ? 'Password has been reset successfully.' : 'Invalid or expired token.',
                    'status_code' => $success ? 200 : 400,
                ]);
            });
        } catch (ModelNotFoundException) {
            return new ApiResource([
                'message' => 'Invalid company email.',
                'status_code' => 400,
            ]);
        }
    }

    public function update(User $user, UserChangePasswordRequest $changePasswordRequest): ApiResource {
        return new ApiResource($this->userService->update($user, $changePasswordRequest->all()));
    }
}
