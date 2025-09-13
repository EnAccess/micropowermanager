<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class UserPasswordController extends Controller {
    public function __construct(
        private UserService $userService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        $this->userService = $userService;
    }

    public function forgotPassword(AdminResetPasswordRequest $request, Response $response): Response {
        $email = $request->input('email');

        try {
            $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);
            $companyId = $databaseProxy->getCompanyId();

            return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($email, $response) {
                if (!$this->userService->resetPassword($email)) {
                    return $response->setStatusCode(422)->setContent(
                        [
                            'data' => [
                                'message' => 'Failed to send password email. Please try it again later.',
                                'status_code' => 409,
                            ],
                        ]
                    );
                }

                return $response->setStatusCode(200)->setContent(
                    [
                        'data' => [
                            'message' => 'Password reset email will be sent to your email if it exists',
                            'status_code' => 200,
                        ],
                    ]
                );
            });
        } catch (ModelNotFoundException $e) {
            return $response->setStatusCode(200)->setContent(
                [
                    'data' => [
                        'message' => 'Password reset email will be sent to your email if it exists',
                        'status_code' => 200,
                    ],
                ]
            );
        }
    }

    public function update(User $user, UserChangePasswordRequest $changePasswordRequest): ApiResource {
        return new ApiResource($this->userService->update($user, $changePasswordRequest->all()));
    }
}
