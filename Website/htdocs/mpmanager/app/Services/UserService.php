<?php

namespace App\Services;

use App\Exceptions\MailNotSentException;
use App\Helpers\MailHelperInterface;
use App\Helpers\PasswordGenerator;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use MPM\User\Events\UserCreatedEvent;

class UserService
{
    public function __construct(private User $user, private MailHelperInterface $mailHelper)
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

    public function resetPassword(string $email): ?User
    {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (Exception $exception) {
            $newPassword = time();
        }

        /** @var User $user */
        $user = $this->buildQuery()->where('email', $email)->firstOrFail();

        if ($user === null) {
            return null;
        }
        $user->update(['password' => $newPassword]);

        try {
            $this->mailHelper->sendViaTemplate(
                $user->getEmail(),
                'Your new Password | Micro Power Manager',
                'templates.mail.forgot_password',
                ["userName" => $user->getName(), 'password' => $newPassword]
            );
        } catch (MailNotSentException $exception) {
            report($exception);
            return null;
        }

        /** @var User|null $user */
        $user = $user->fresh()->with(['addressDetails'])->first();

        return $user;
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

    public function getById($id)
    {
         return  $this->user->newQuery()->find($id);
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null)
    {
        // TODO: Implement getAll() method.
    }
}
