<?php

namespace App\Services;

use App\Exceptions\MailNotSentException;
use App\Helpers\PasswordGenerator;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService implements IUserService
{
    private $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return Builder|Model
     */
    public function create(array $userData)
    {
        $user =  $this->user->newQuery()->create([
            'name' => $userData['name'],
            'password' => $userData['password'],
            'email' => $userData['email']
        ]);
        return $this->user->newQuery()->with(['addressDetails'])->find($user->id);
    }

    public function update($user, $data)
    {
        $user->update(['password' => $data['password']]);
        return $user->fresh();
    }

    public function resetPassword(string $email)
    {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (Exception $exception) {
            $newPassword = time();
        }

        try {
            $user = User::query()->where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException $x) {
            return null;
        }
        $user->update(['password' => $newPassword]);


        //send the new password
        //this part can not extracted as a job, jobs are working async and in case of any issues the system wont be
        // able to send bad http status
        $mailer = resolve('MailProvider');
        try {
            $mailer->sendPlain(
                $user->email,
                'Your new Password | Micro Power Manager',
                'You can use ' . $newPassword . ' to Login. <br> Please don\'t forget to change your password.'
            );
        } catch (MailNotSentException $exception) {
            Log::debug(
                'Failed to reset password',
                [
                    'id' => '4
                78efhd3497gvfdhjkwgsdjkl4ghgdf',
                    'message' => 'Password reset email for ' . $user->email . ' failed',
                    'reason' => $exception->getMessage(),
                ]
            );
            return null;
        }

        return $user->fresh()->with(['addressDetails']);
    }

    public function list()
    {
        return User::query()->select('id', 'name', 'email')->with(['addressDetails'])->paginate();
    }

    public function get($id)
    {
        return User::with(['addressDetails'])
            ->where('id', $id)->firstOrFail();
    }

    public function resetAdminPassword()
    {
        $firstUser = User::query()->get()->first();
        $randomPassword = str_random(8);
        $firstUser->update(['password' => $randomPassword]) ;
        $firstUser->save();

        $admin['email'] = $firstUser->email;
        $admin['password'] = $randomPassword;

        return $admin;
    }
}
