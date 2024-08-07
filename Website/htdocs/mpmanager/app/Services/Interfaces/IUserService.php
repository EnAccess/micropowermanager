<?php

namespace App\Services\Interfaces;

interface IUserService
{
    public function create(array $userData);

    public function update($user, $data);

    public function resetPassword(string $email);

    public function list();

    public function get($id);
}
