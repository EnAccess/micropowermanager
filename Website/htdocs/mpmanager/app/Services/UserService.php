<?php

namespace App\Services;

use App\Exceptions\MailNotSentException;
use App\Helpers\PasswordGenerator;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use MPM\User\Events\UserCreatedEvent;

class UserService
{
    public function __construct(private User $user)
    {
    }

    public function create(array $userData, ?int $companyId = null): User
    {
        $shouldSyncUserWithMasterDatabase = $companyId !== null;
        $companyId = $companyId ?? auth('api')->payload()->get('companyId');

        /** @var User $user */
        $user = $this->buildQuery()->newQuery()->create([
            'name' => $userData['name'],
            'password' => $userData['password'],
            'email' => $userData['email'],
            'company_id' => $companyId,
        ]);

        event(new UserCreatedEvent($user, $shouldSyncUserWithMasterDatabase));

        return $user;
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
            $user = $this->buildQuery()
                ->where('email', $email)
                ->firstOrFail();
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

    public function list(): LengthAwarePaginator
    {
        return $this->buildQuery()
            ->select('id', 'name', 'email')
            ->with(['addressDetails'])
            ->paginate();
    }

    public function get($id): User
    {
        /** @var User $user */
        $user = User::with(['addressDetails'])
            ->where('id', '=', $id)
            ->firstOrFail();

        return $user;
    }

    public function resetAdminPassword(): array
    {
        /** @var User $user */
        $user = $this->buildQuery()->first();
        $randomPassword = str_random(8);
        $user->update(['password' => $randomPassword]);
        $user->save();

        $admin['email'] = $user->email;
        $admin['password'] = $randomPassword;

        return $admin;
    }

    private function buildQuery(): Builder
    {
        return $this->user->newQuery();
    }

    public function getCompanyId(): int
    {
        /** @var User $user */
        $user = $this->buildQuery()
            ->select(User::COL_COMPANY_ID)
            ->first();

        return $user->getCompanyId();
    }
}
