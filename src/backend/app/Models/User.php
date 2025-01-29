<?php

namespace App\Models;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    use Notifiable;
    use HasFactory;

    public const RELATION_NAME = 'admin';
    public const COL_ID = 'id';
    public const COL_COMPANY_ID = 'company_id';

    public function __construct(array $attributes = []) {
        $this->setConnection('tenant');

        parent::__construct($attributes);
    }

    public function setPasswordAttribute($password): void {
        $this->attributes['password'] = Hash::make($password);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
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
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    // we need to provide the company id in the token to encode and find the right database when an authenticated requests hits the api
    public function getJWTCustomClaims(): array {
        return [
            'companyId' => $this->getCompanyId(),
        ];
    }

    public function address(): MorphOne {
        return $this->morphOne(Address::class, 'owner');
    }

    public function addressDetails(): MorphOne {
        return $this->address()->with('city');
    }

    public function balanceHistory(): HasMany {
        return $this->hasMany(AgentBalanceHistory::class);
    }

    public function assignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class);
    }

    // belongsTo company
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

    public function relationTicketUser(): HasOne {
        return $this->hasOne(TicketUser::class, TicketUser::COL_USER_ID, User::COL_ID);
    }
}
