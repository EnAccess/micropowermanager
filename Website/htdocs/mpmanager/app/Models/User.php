<?php

namespace App\Models;

use App\Models\Address\Address;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class User
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $company_id
 *
 */
class User extends Authenticatable implements JWTSubject
{
    public function __construct(array $attributes = [])
    {
        $this->setConnection('shard');
        parent::__construct($attributes);
    }

    use Notifiable;

    public const COL_COMPANY_ID = 'company_id';

    public function setPasswordAttribute($password): void
    {
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
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    //we need to provide the company id in the token to encode and find the right database when an authenticated requests hits the api
    public function getJWTCustomClaims(): array
    {
        return [
            'companyId' => $this->getCompanyId()
        ];
    }


    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'owner');
    }

    public function addressDetails()
    {
        return $this->address()->with('city');
    }
    public function balanceHistory(): HasMany
    {
        return $this->hasMany(AgentBalanceHistory::class);
    }
    public function assignedAppliance(): HasMany
    {
        return $this->hasMany(AgentAssignedAppliances::class);
    }
    // belongsTo company
    public function company(): BelongsTo
    {
        return $this->BelongsTo(Company::class, 'company_id');
    }


    public function getCompanyId(): int
    {
        return $this->company_id;
    }
}
