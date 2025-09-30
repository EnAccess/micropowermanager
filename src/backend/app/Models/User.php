<?php

namespace App\Models;

use App\Models\Address\Address;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Inensus\Ticket\Models\TicketUser;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User.
 *
 * @property int         $id
 * @property int         $company_id
 * @property string      $name
 * @property string|null $email
 */
class User extends Authenticatable implements JWTSubject {
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    public const RELATION_NAME = 'users';
    public const COL_ID = 'id';
    public const COL_COMPANY_ID = 'company_id';

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = []) {
        $this->setConnection('tenant');

        parent::__construct($attributes);
    }

    public function setPasswordAttribute(string $password): void {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier(): mixed {
        return $this->getKey();
    }

    /**
     * Get custom claims for JWT token.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array {
        return [
            'companyId' => $this->getCompanyId(),
        ];
    }

    /**
     * @return MorphOne<Address, $this>
     */
    public function address(): MorphOne {
        return $this->morphOne(Address::class, 'owner');
    }

    /**
     * @return MorphOne<Address, $this>
     */
    public function addressDetails(): MorphOne {
        return $this->address()->with('city');
    }

    /**
     * @return HasMany<AgentBalanceHistory, $this>
     */
    public function balanceHistory(): HasMany {
        return $this->hasMany(AgentBalanceHistory::class);
    }

    /**
     * @return HasMany<AgentAssignedAppliances, $this>
     */
    public function assignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class);
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo {
        return $this->BelongsTo(Company::class, 'company_id');
    }

    public function getCompanyId(): int {
        return $this->company_id;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getEmail(): string {
        return $this->email;
    }

    /**
     * @return HasOne<TicketUser, $this>
     */
    public function relationTicketUser(): HasOne {
        return $this->hasOne(TicketUser::class, TicketUser::COL_USER_ID, User::COL_ID);
    }
}
