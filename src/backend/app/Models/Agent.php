<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Inensus\Ticket\Models\Ticket;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * Class Agent.
 *
 * @property int    $id
 * @property int    $person_id
 * @property int    $mini_grid_id
 * @property int    $agent_commission_id
 * @property string $device_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $fire_base_token
 * @property float  $balance
 * @property float  $available_balance
 * @property string $remember_token
 * @property int    $company_id
 */
class Agent extends Authenticatable implements JWTSubject {
    use HasFactory;

    public const RELATION_NAME = 'agent';

    public function __construct(array $attributes = []) {
        $this->setConnection('shard');
        parent::__construct($attributes);
    }

    public function setPasswordAttribute($password): void {
        $this->attributes['password'] = Hash::make($password);
    }

    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_id',
    ];

    public function miniGrid(): BelongsTo {
        return $this->belongsTo(MiniGrid::class);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     *
     * @psalm-return array<empty, empty>
     */
    public function getJWTCustomClaims() {
        return [
            'companyId' => User::query()->select(User::COL_COMPANY_ID)->first()[User::COL_COMPANY_ID],
        ];
    }

    public function address(): MorphOne {
        return $this->morphOne(Address::class, 'owner');
    }

    public function tickets(): MorphMany {
        return $this->morphMany(Ticket::class, 'creator');
    }

    public function transaction(): HasMany {
        return $this->hasMany(Transaction::class);
    }

    public function balanceHistory(): HasMany {
        return $this->hasMany(AgentBalanceHistory::class);
    }

    public function assignedAppliance(): HasMany {
        return $this->hasMany(AgentAssignedAppliances::class);
    }

    public function addressDetails() {
        return $this->addresses()->with('city');
    }

    public function person(): BelongsTo {
        return $this->belongsTo(Person::class);
    }

    public function commission(): BelongsTo {
        return $this->belongsTo(AgentCommission::class, 'agent_commission_id');
    }

    public function soldAppliances(): MorphMany {
        return $this->morphMany(AssetPerson::class, 'creator');
    }

    public function agentCharges(): HasMany {
        return $this->hasMany(AgentCharge::class);
    }

    public function addresses(): MorphMany {
        return $this->morphMany(Address::class, 'owner');
    }

    public function receipt(): HasMany {
        return $this->hasMany(AgentReceipt::class, 'agent_id', 'id');
    }
}
