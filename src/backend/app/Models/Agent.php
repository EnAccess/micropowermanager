<?php

namespace App\Models;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
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
 * @property string $mobile_device_id
 * @property string $email
 * @property string $password
 * @property string $fire_base_token
 * @property float  $balance
 * @property float  $available_balance
 * @property string $remember_token
 * @property int    $company_id
 */
class Agent extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<Agent> */
    use HasFactory;

    public const RELATION_NAME = 'agent';

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->setConnection('tenant');
        parent::__construct($attributes);
    }

    public function setPasswordAttribute(string $password): void
    {
        $this->attributes['password'] = Hash::make($password);
    }

    protected $guarded = [];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'mobile_device_id',
    ];

    /**
     * @return BelongsTo<MiniGrid, Agent>
     */
    public function miniGrid(): BelongsTo
    {
        return $this->belongsTo(MiniGrid::class);
    }

    /**
     * @return int|string|null
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     *
     * @psalm-return array{companyId: mixed}
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'companyId' => User::query()->select(User::COL_COMPANY_ID)->first()[User::COL_COMPANY_ID],
        ];
    }

    /**
     * @return MorphOne<Address, Agent>
     */
    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'owner');
    }

    /**
     * @return MorphMany<Ticket, Agent>
     */
    public function tickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'creator');
    }

    /**
     * @return HasMany<Transaction, Agent>
     */
    public function transaction(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<AgentBalanceHistory, Agent>
     */
    public function balanceHistory(): HasMany
    {
        return $this->hasMany(AgentBalanceHistory::class);
    }

    /**
     * @return HasMany<AgentAssignedAppliances, Agent>
     */
    public function assignedAppliance(): HasMany
    {
        return $this->hasMany(AgentAssignedAppliances::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function addressDetails()
    {
        return $this->addresses()->with('city');
    }

    /**
     * @return BelongsTo<Person, Agent>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<AgentCommission, Agent>
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(AgentCommission::class, 'agent_commission_id');
    }

    /**
     * @return MorphMany<AssetPerson, Agent>
     */
    public function soldAppliances(): MorphMany
    {
        return $this->morphMany(AssetPerson::class, 'creator');
    }

    /**
     * @return HasMany<AgentCharge, Agent>
     */
    public function agentCharges(): HasMany
    {
        return $this->hasMany(AgentCharge::class);
    }

    /**
     * @return MorphMany<Address, Agent>
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'owner');
    }

    /**
     * @return HasMany<AgentReceipt, Agent>
     */
    public function receipt(): HasMany
    {
        return $this->hasMany(AgentReceipt::class, 'agent_id', 'id');
    }
}
