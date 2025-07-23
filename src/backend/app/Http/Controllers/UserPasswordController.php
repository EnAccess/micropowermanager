<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\UserChangePasswordRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Response;

class UserPasswordController extends Controller {
    private UserService $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function forgotPassword(AdminResetPasswordRequest $request, Response $response): Response {
        if (!$this->userService->resetPassword($request->input('email'))) {
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
                    'message' => 'New password sent to your email address',
                    'status_code' => 200,
                ],
            ]
        );
    }

    public function update(User $user, UserChangePasswordRequest $changePasswordRequest): ApiResource {
        return new ApiResource($this->userService->update($user, $changePasswordRequest->all()));
    }
}
