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
 */
class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    protected $connection = 'micro_power_manager';

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

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
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
}
