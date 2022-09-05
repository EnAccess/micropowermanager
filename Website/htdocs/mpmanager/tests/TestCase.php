<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Env;
use Tymon\JWTAuth\Facades\JWTAuth;


abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;


    protected function setUp(): void
    {
        parent::setUp();
        config()->set('PHP_UNIT', true);

    }

    /**
     * generates an jwt for the given user
     * if user is not present it tries to generate a token for the first user
     */
    protected function headers(User $user = null)
    {
        if (!$user) {
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'johndoe@example.com',
                'password' => '1234512345'
            ]);
        }
        $token = JWTAuth::fromUser($user);

        JWTAuth::setToken($token);

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ];


    }
}
