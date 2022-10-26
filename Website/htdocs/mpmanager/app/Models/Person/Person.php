<?php

namespace App\Models\Person;

use App\Events\PersonDeleting;
use App\Models\Address\Address;
use App\Models\Address\HasAddressesInterface;
use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\BaseModel;
use App\Models\Country;
use App\Models\CustomerGroup;
use App\Models\Meter\MeterParameter;
use App\Models\PaymentHistory;
use App\Models\Role\RoleInterface;
use App\Models\Role\Roles;
use Doctrine\DBAL\Query\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Inensus\Ticket\Models\Ticket;

/**
 * Class Person
 *
 * @package  App
 * @property int $id
 * @property string $title
 * @property string $education
 * @property string $name
 * @property string $surname
 * @property mixed $birth_date
 * @property string $sex TODO: replace with gender
 * @property int $nationality
 * @property int $is_customer
 */
class Person extends BaseModel implements HasAddressesInterface, RoleInterface
{
    use SoftDeletes;


    protected $guarded = [];

    protected $casts = [
        'additional_json' => 'array'
    ];

    protected $dispatchesEvents = [
        'deleting' => PersonDeleting::class,
    ];


    public function tickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'owner');
    }

    public function saveAddress(Address $address): void
    {
        $this->addresses()->save($address);
    }

    /**
     * @return MorphMany
     */
    public function addresses(): HasOneOrMany
    {
        return $this->morphMany(Address::class, 'owner');
    }

    public function citizenship(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality', 'id');
    }


    public function meters(): MorphMany
    {
        return $this->morphMany(MeterParameter::class, 'owner');
    }

    /**
     * @return MorphMany
     */
    public function roleOwner(): HasOneOrMany
    {
        return $this->morphMany(Roles::class, 'role_owner');
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(PaymentHistory::class, 'payer');
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::Class);
    }

    public function agentSoldAppliance(): HasOne
    {
        return $this->hasOne(AgentSoldAppliance::Class);
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->name, $this->surname);
    }

    public function livingInClusterQuery(int $clusterId)
    {
        return DB::table($this->getTable())
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

    public function getId(): int
    {
        return $this->id;
    }
}
