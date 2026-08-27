<?php

namespace Tests\Feature;

use App\Exceptions\EntityHasChildrenException;
use App\Models\Address\Address;
use App\Models\City;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use Database\Factories\CityFactory;
use Database\Factories\ClusterFactory;
use Database\Factories\MiniGridFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class CityTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $person;
    private array $clusterIds = [];
    private array $miniGridIds = [];
    private array $cityIds = [];

    public function testUserGetsCities(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $this->createTestData($clusterCount, $miniGridCount);
        $response = $this->actingAs($this->user)->get('/api/cities');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->cityIds));
    }

    public function testUserGetsCityById(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/cities/%s', $this->cityIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->cityIds[0]);
    }

    public function testUserCreatesNewCity(): void {
        $clusterCount = 1;
        $miniGridCount = 1;
        $cityCount = 1;
        $this->createTestData($clusterCount, $miniGridCount, $cityCount);
        $cityData = [
            'mini_grid_id' => $this->miniGridIds[0],
            'country_id' => 1,
            'geo_json' => $this->pointFeature(-7.873645, 39.754433),
            'name' => $this->faker->city(),
        ];
        $response = $this->actingAs($this->user)->post('/api/cities', $cityData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $cityData['name']);

        $city = City::query()->find($response['data']['id']);
        $this->assertEquals([39.754433, -7.873645], $city->location->geo_json->geometry->coordinates);
    }

    public function testUserSoftDeletesAnUnusedCity(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];

        $response = $this->actingAs($this->user)->delete("/api/cities/{$cityId}");

        $response->assertStatus(200);
        $this->assertNull(City::query()->find($cityId));
        $this->assertNotNull(City::withTrashed()->find($cityId)->deleted_at);
    }

    public function testCityDeleteBlockedWhenItHasAddresses(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];

        $this->makeAddress(PersonFactory::new()->create(), $cityId, isPrimary: 1);

        $this->withoutExceptionHandling();
        $this->expectException(EntityHasChildrenException::class);

        try {
            $this->actingAs($this->user)->delete("/api/cities/{$cityId}");
        } finally {
            $this->assertNotNull(City::query()->find($cityId));
        }
    }

    public function testCityDeleteBlockedMessageNamesTheLinkedRecords(): void {
        $this->createTestData(1, 1, 2);
        [$oldCityId, $newCityId] = $this->cityIds;

        $mover = PersonFactory::new()->create();
        $this->makeAddress($mover, $oldCityId, isPrimary: 0);
        $this->makeAddress($mover, $newCityId, isPrimary: 1);
        $this->makeAddress(PersonFactory::new()->create(), $oldCityId, isPrimary: 1);

        $response = $this->actingAs($this->user)->deleteJson("/api/cities/{$oldCityId}");

        $response->assertStatus(422);
        $this->assertStringContainsString('2 addresses still point to it', $response['message']);
        $this->assertStringContainsString('2 customers, 1 of them through a former address', $response['message']);
        $this->assertNotNull(City::query()->find($oldCityId));
    }

    public function testCityDeleteDiscardsAddressesWhoseOwnerIsGone(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];

        $person = PersonFactory::new()->create();
        $address = $this->makeAddress($person, $cityId, isPrimary: 1);
        // Mass delete bypasses PersonObserver, so the address is left dangling.
        Person::query()->where('id', $person->id)->forceDelete();

        $response = $this->actingAs($this->user)->deleteJson("/api/cities/{$cityId}");

        $response->assertStatus(200);
        $this->assertNull(City::query()->find($cityId));
        $this->assertNull(Address::query()->find($address->id));
    }

    public function testCityDeleteDiscardsAddressesOfSoftDeletedCustomers(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];

        $person = PersonFactory::new()->create();
        $address = $this->makeAddress($person, $cityId, isPrimary: 1);
        Person::query()->where('id', $person->id)->delete();
        $this->assertNotNull(Person::withTrashed()->find($person->id)->deleted_at);

        $response = $this->actingAs($this->user)->deleteJson("/api/cities/{$cityId}");

        $response->assertStatus(200);
        $this->assertNull(City::query()->find($cityId));
        $this->assertNull(Address::query()->find($address->id));
    }

    public function testAddressesOfSoftDeletedCustomersAreNotListedAsLinked(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];

        $activePerson = PersonFactory::new()->create();
        $activeAddress = $this->makeAddress($activePerson, $cityId, isPrimary: 1);
        $deletedPerson = PersonFactory::new()->create();
        $this->makeAddress($deletedPerson, $cityId, isPrimary: 1);
        Person::query()->where('id', $deletedPerson->id)->delete();

        $response = $this->actingAs($this->user)->getJson("/api/cities/{$cityId}/addresses");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($activeAddress->id, $response['data'][0]['id']);
    }

    public function testUserDeletesCityAfterReassigningItsAddresses(): void {
        $this->createTestData(1, 1, 2);
        [$sourceCityId, $targetCityId] = $this->cityIds;

        $address = $this->makeAddress(PersonFactory::new()->create(), $sourceCityId, isPrimary: 1);
        $deletedPerson = PersonFactory::new()->create();
        $residueAddress = $this->makeAddress($deletedPerson, $sourceCityId, isPrimary: 1);
        Person::query()->where('id', $deletedPerson->id)->delete();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/cities/{$sourceCityId}", ['reassign_addresses_to' => $targetCityId]);

        $response->assertStatus(200);
        $this->assertNull(City::query()->find($sourceCityId));
        $this->assertEquals($targetCityId, $address->fresh()->city_id);
        // Residue must not be carried over into the village that survives.
        $this->assertNull(Address::query()->find($residueAddress->id));
    }

    public function testCityDeleteRejectsReassigningAddressesToItself(): void {
        $this->createTestData();
        $cityId = $this->cityIds[0];
        $this->makeAddress(PersonFactory::new()->create(), $cityId, isPrimary: 1);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/cities/{$cityId}", ['reassign_addresses_to' => $cityId]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('reassign_addresses_to');
        $this->assertNotNull(City::query()->find($cityId));
    }

    public function testUserGetsAddressesLinkedToACity(): void {
        $this->createTestData(1, 1, 2);
        [$oldCityId, $newCityId] = $this->cityIds;

        $mover = PersonFactory::new()->create();
        $formerAddress = $this->makeAddress($mover, $oldCityId, isPrimary: 0);
        $this->makeAddress($mover, $newCityId, isPrimary: 1);

        $response = $this->actingAs($this->user)->getJson("/api/cities/{$oldCityId}/addresses");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($formerAddress->id, $response['data'][0]['id']);
        $this->assertFalse($response['data'][0]['is_primary']);
        $this->assertEquals('person', $response['data'][0]['owner_type']);
        $this->assertEquals("{$mover->name} {$mover->surname}", $response['data'][0]['owner_name']);
    }

    public function testUserUpdatesACity(): void {
        $clusterCount = 2;
        $miniGridCount = 2;
        $this->createTestData($clusterCount, $miniGridCount);
        $city = City::query()->first();
        $this->assertNull($city->location);
        $cityData = [
            'name' => 'updatedName',
            'mini_grid_id' => $this->miniGridIds[1],
            'country_id' => 1,
            'geo_json' => $this->pointFeature(-7.873645, 39.754433),
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/cities/%s', $city->id), $cityData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $cityData['name']);
        $this->assertEquals($this->miniGridIds[1], $city->fresh()->mini_grid_id);
        // The village had no location yet — the update must create one.
        $this->assertEquals([39.754433, -7.873645], $city->fresh()->location->geo_json->geometry->coordinates);
    }

    public function testCityUpdateRejectsMalformedGeoJson(): void {
        $this->createTestData();
        $city = City::query()->first();

        $singleCoordinate = $this->pointFeature(-7.873645, 39.754433);
        $singleCoordinate['geometry']['coordinates'] = [39.754433];
        $this->actingAs($this->user)
            ->putJson(sprintf('/api/cities/%s', $city->id), ['geo_json' => $singleCoordinate])
            ->assertStatus(422);

        $outOfRange = $this->pointFeature(-100.0, 39.754433);
        $this->actingAs($this->user)
            ->putJson(sprintf('/api/cities/%s', $city->id), ['geo_json' => $outOfRange])
            ->assertStatus(422);

        $this->assertNull($city->fresh()->location);
    }

    protected function createTestData($clusterCount = 1, $miniGridCount = 1, $cityCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->assignRole($this->user, 'admin');

        while ($clusterCount > 0) {
            $cluster = ClusterFactory::new()->create([
                'name' => $this->faker->unique()->companySuffix(),
                'manager_id' => $this->user->id,
            ]);
            $this->clusterIds[] = $cluster->id;

            while ($miniGridCount > 0) {
                $geographicalInformation = GeographicalInformation::query()->make(['geo_json' => GeographicalInformation::pointFromString('111,222')]);
                $miniGrid = MiniGridFactory::new()->create([
                    'cluster_id' => $cluster->id,
                    'name' => $this->faker->unique()->companySuffix(),
                ]);

                while ($cityCount > 0) {
                    $city = CityFactory::new()->create([
                        'country_id' => 1,
                        'mini_grid_id' => $miniGrid->id,
                    ]);
                    $this->cityIds[] = $city->id;
                    --$cityCount;
                }

                $geographicalInformation->owner()->associate($miniGrid);
                $geographicalInformation->save();
                $this->miniGridIds[] = $miniGrid->id;
                --$miniGridCount;
            }

            --$clusterCount;
        }
    }

    private function makeAddress(Person $owner, int $cityId, int $isPrimary): Address {
        $address = Address::query()->make([
            'city_id' => $cityId,
            'is_primary' => $isPrimary,
        ]);
        $address->owner()->associate($owner)->save();

        return $address;
    }

    protected function generateUniqueNumber(): int {
        return $this->faker->unique()->randomNumber() + $this->faker->unique()->randomNumber() +
            $this->faker->unique()->randomNumber();
    }

    /**
     * @return array<string, mixed>
     */
    private function pointFeature(float $latitude, float $longitude): array {
        return json_decode(json_encode(GeographicalInformation::makePoint($latitude, $longitude)), true);
    }
}
