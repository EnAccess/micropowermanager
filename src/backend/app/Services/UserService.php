<?php

namespace App\Services;

use App\Events\UserCreatedEvent;
use App\Exceptions\MailNotSentException;
use App\Helpers\MailHelper;
use App\Helpers\PasswordGenerator;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tymon\JWTAuth\JWTGuard;

class UserService {
    public function __construct(
        private User $user,
        private MailHelper $mailHelper,
    ) {}

    /**
     * @param array{name: string, password: string, email: string} $userData
     */
    public function create(array $userData, ?int $companyId = null): User {
        $shouldSyncUserWithMasterDatabase = $companyId !== null;

        if ($companyId === null) {
            /** @var JWTGuard $guard */
            $guard = auth('api');
            $payload = $guard->check() ? $guard->payload() : null;
            $companyId = $payload?->get('companyId');
        }

        $user = $this->user->newQuery()->create([
            'name' => $userData['name'],
            'password' => $userData['password'],
            'email' => $userData['email'],
            'company_id' => $companyId,
        ]);

        event(new UserCreatedEvent($user, $shouldSyncUserWithMasterDatabase));

        return $user;
    }

    /**
     * @param array{password?: string, name?: string} $data
     */
    public function update(User $user, array $data): User {
        $updateData = [];
        if (isset($data['password'])) {
            $updateData['password'] = $data['password'];
        }
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        return $user->fresh();
    }

    public function resetPassword(string $email): ?User {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (\Exception $exception) {
            $newPassword = time();
        }

        $user = $this->user->newQuery()->where('email', $email)->firstOrFail();

        if ($user == null) {
            return null;
        }

        $user->update(['password' => $newPassword]);

        try {
            $this->mailHelper->sendViaTemplate(
                $user->getEmail(),
                'Your new Password | Micro Power Manager',
                'templates.mail.forgot_password',
                ['userName' => $user->getName(), 'password' => $newPassword]
            );
        } catch (MailNotSentException $exception) {
            report($exception);

            return null;
        }

        return $user->fresh()->with(['addressDetails'])->first();
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function list(): LengthAwarePaginator {
        return $this->user->newQuery()
            ->select('id', 'name', 'email')
            ->with(['addressDetails', 'roles:name'])
            ->paginate();
    }

    public function get(int $id): User {
        return User::with(['addressDetails'])
            ->where('id', '=', $id)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function resetAdminPassword(): array {
        $user = $this->user->newQuery()->first();
        $randomPassword = str_random(8);
        $user->update(['password' => $randomPassword]);
        $user->save();

        $admin['email'] = $user->email;
        $admin['password'] = $randomPassword;

        return $admin;
    }

    public function getCompanyId(): int {
        $user = $this->user->newQuery()
            ->select(User::COL_COMPANY_ID)
            ->first();

        return $user->getCompanyId();
    }

    public function getById(int $id): ?User {
        return $this->user->newQuery()->find($id);
    }

    public function getByEmail(string $email): ?User {
        return $this->user->newQuery()->where('email', $email)->first();
    }

    public function delete(User $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, User>
     */
    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection {
        return $this->user->newQuery()->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersWithRolesAndPermissions(): Collection {
        return $this->user->newQuery()->with(['roles.permissions'])->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersToSendOutstandingDebtsReport(): Collection {
        return $this->user->newQuery()->whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'owner', 'financial-manager']);
        })->get();
    }
}
