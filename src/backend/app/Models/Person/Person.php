<?php

namespace App\Models\Person;

use App\Events\PersonDeleting;
use App\Models\Address\Address;
use App\Models\Address\HasAddressesInterface;
use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use App\Models\Base\BaseModel;
use App\Models\Country;
use App\Models\CustomerGroup;
use App\Models\Device;
use App\Models\PaymentHistory;
use App\Models\Role\RoleInterface;
use App\Models\Role\Roles;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Inensus\Ticket\Models\Ticket;

/**
 * Class Person.
 *
 * @property int    $id
 * @property string $title
 * @property string $education
 * @property string $name
 * @property string $surname
 * @property mixed  $birth_date
 * @property string $sex         TODO: replace with gender
 * @property int    $nationality
 * @property int    $is_customer
 */
class Person extends BaseModel implements HasAddressesInterface, RoleInterface {
    use HasFactory;
    use SoftDeletes;

    public const RELATION_NAME = 'person';

    protected $guarded = [];

    protected $appends = ['is_active'];

    protected $casts = [
        'additional_json' => 'array',
    ];

    /** @var array<string, string> */
    protected $dispatchesEvents = [
        'deleting' => PersonDeleting::class,
    ];

    /**
     * @return MorphMany<Ticket, $this>
     */
    public function tickets(): MorphMany {
        return $this->morphMany(Ticket::class, 'owner');
    }

    public function saveAddress(Address $address): void {
        $this->addresses()->save($address);
    }

    /**
     * @return MorphMany<Address, $this>
     */
    public function addresses(): HasOneOrMany {
        return $this->morphMany(Address::class, 'owner');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function citizenship(): BelongsTo {
        return $this->belongsTo(Country::class, 'nationality', 'id');
    }

    /**
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany {
        return $this->hasMany(Device::class);
    }

    /**
     * @return MorphMany<Roles, $this>
     */
    public function roleOwner(): HasOneOrMany {
        return $this->morphMany(Roles::class, 'role_owner');
    }

    /**
     * @return MorphMany<PaymentHistory, $this>
     */
    public function payments(): MorphMany {
        return $this->morphMany(PaymentHistory::class, 'payer');
    }

    /**
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * @return HasOne<Agent, $this>
     */
    public function agent(): HasOne {
        return $this->hasOne(Agent::class);
    }

    /**
     * @return HasOne<AgentSoldAppliance, $this>
     */
    public function agentSoldAppliance(): HasOne {
        return $this->hasOne(AgentSoldAppliance::class);
    }

    /**
     * @return HasMany<AssetPerson, $this>
     */
    public function assetPerson(): HasMany {
        return $this->HasMany(AssetPerson::class, 'person_id', 'id');
    }

    public function __toString(): string {
        return sprintf('%s %s', $this->name, $this->surname);
    }

    public function livingInClusterQuery(int $clusterId): Builder {
        return DB::connection('tenant')->table($this->getTable())
            ->select('people.id')
            ->leftJoin('addresses', function (JoinClause $q) {
                $q->on('addresses.owner_id', '=', 'people.id');
                $q->where('addresses.owner_type', 'person');
                $q->where('addresses.is_primary', '=', 1);
            })
            ->leftJoin('cities', function (JoinClause $jc) {
                $jc->on('cities.id', '=', 'addresses.city_id');
            })
            ->leftJoin('clusters', function (JoinClause $jc) {
                $jc->on('clusters.id', '=', 'cities.cluster_id');
            })->where('clusters.id', '=', $clusterId)
            ->orderBy('people.id')
            ->orderBy('cities.id');
    }

    public function getId(): int {
        return $this->id;
    }

    public function getIsActiveAttribute(): bool {
        $lastPayment = $this->latestPayment;

        if (!$lastPayment) {
            return false;
        }

        return Carbon::parse($lastPayment->created_at)->diffInDays(now()) <= 25;
    }

    /**
     * @return HasOne<PaymentHistory, $this>
     */
    public function latestPayment(): HasOne {
        return $this->hasOne(PaymentHistory::class, 'payer_id')->latestOfMany('created_at');
    }
}
