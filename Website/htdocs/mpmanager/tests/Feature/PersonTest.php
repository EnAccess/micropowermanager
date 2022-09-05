<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\Company;
use App\Models\MaintenanceUsers;
use App\Models\Person\Person;
use App\Models\User;
use Database\Factories\AddressFactory;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\PaymentHistoryFactory;
use Database\Factories\PersonFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Database\Factories\VodacomTransactionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PersonTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;


    public function test_user_gets_customer_list()
    {
        /** @var User $user */
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        PersonFactory::new()->create();
        PersonFactory::new()->create();
        PersonFactory::new()->create([
            'title' => $this->faker->title('male'),
            'name' => $this->faker->firstName(),
            'surname' => $this->faker->firstName(),
            'birth_date' => $this->faker->date(),
            'sex' => $this->faker->randomKey(['male', 'female']),
            'is_customer' => 0,
        ]);
        $response = $this->actingAs($user)->get('/api/people');
        $response->assertStatus(200);
        $this->assertEquals(2, count($response['data']));
    }

    public function test_user_gets_all_registered_person_list()
    {
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $person = PersonFactory::new()->create();
        $person = PersonFactory::new()->create();
        $response = $this->actingAs($user)->get('/api/people/all');
        $response->assertStatus(200);
        $this->assertEquals(2, count($response['data']));
    }

    public function test_user_get_person_by_id()
    {
        /** @var User $user */
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        $person = PersonFactory::new()->create();

        $response = $this->actingAs($user)->get(sprintf('/api/people/%s', $person->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $person->name);
    }

    public function test_user_can_create_new_person_as_a_customer()
    {
        $this->withExceptionHandling();
        /** @var User $user */
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        $postData = [
            'email' => $this->faker->email,
            'phone' => "254231232132",
            'street' => $this->faker->streetName,
            'city_id' => 1,
            'is_primary' => 1,
            'title' => 'Mr',
            'education' => 'Bachelor',
            'name' => $this->faker->firstName,
            'surname' => $this->faker->lastName,
            'birth_date' => '1990-01-01',
            'sex' => 'male',
            'customer_type' => 'customer',
            'is_customer' => 1
        ];
        $response = $this->actingAs($user)->post('/api/people/', $postData);
        $response->assertStatus(201);
        $lastCreatedPerson = Person::query()->latest()->first();
        $personAddress = $lastCreatedPerson->addresses()->first();
        $this->assertEquals($lastCreatedPerson->id, $response['data']['id']);
        $this->assertEquals($personAddress->street, $postData['street']);

    }

    public function test_user_can_create_new_person_as_a_maintenance_user()
    {
        $this->withExceptionHandling();
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $name = $this->faker->firstName;
        $postData = [
            'email' => $this->faker->email,
            'phone' => "254231232132",
            'street' => $this->faker->streetName,
            'city_id' => 1,
            'mini_grid_id' => 1,
            'is_primary' => 1,
            'title' => 'Mr',
            'education' => 'Bachelor',
            'name' => $name,
            'surname' => $this->faker->lastName,
            'birth_date' => '1990-01-01',
            'sex' => 'male',
            'customer_type' => 'maintenance'

        ];
        $response = $this->actingAs($user)->post('/api/people/', $postData);
        $response->assertStatus(201);
        $lastCreatedPerson = Person::query()->latest()->first();
        $personAddress = $lastCreatedPerson->addresses()->first();
        $maintenanceUser = MaintenanceUsers::query()->latest()->first();
        $this->assertEquals(0, $lastCreatedPerson->is_customer);
        $this->assertEquals($personAddress->phone, $postData['phone']);
        $this->assertEquals($maintenanceUser->person->name, $postData['name']);

    }

    public function test_user_can_update_a_person()
    {

        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $person = PersonFactory::new()->create();
        $putData = [
            'name' => 'updated name',
            'surname' => 'updated surname',
            'sex' => 'female',
        ];
        $response = $this->actingAs($user)->put(sprintf('/api/people/%s', $person->id), $putData);
        $response->assertStatus(200);
        $updatedPerson = Person::query()->first();
        $this->assertEquals($updatedPerson->education, $person->education);
        $this->assertEquals($updatedPerson->title, $person->title);
        $this->assertEquals($updatedPerson->birth_date, $person->birth_date);
        $this->assertEquals($updatedPerson->name, 'updated name');
        $this->assertEquals($updatedPerson->surname, 'updated surname');

    }

    public function test_user_can_search_a_person_by_name()
    {
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $person = PersonFactory::new()->create();
        $response = $this->actingAs($user)->get('/api/people/search?q=' . $person->name);
        $this->assertEquals($response['data'][0]['id'], $person->id);
    }

    public function test_user_can_search_a_person_by_phone_number()
    {
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        PersonFactory::new()->create();
        $address = AddressFactory::new()->create([
            'phone' => '254231232132',
        ]);
        $response = $this->actingAs($user)->get('/api/people/search?q=' . $address->phone);
        $responseData = $response['data'][0];
        $this->assertEquals($responseData['addresses'][0]['phone'], $address->phone);
    }

    public function test_user_can_get_persons_transactions()
    {
        /** @var User $user */
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        $person = PersonFactory::new()->create();
        $address = AddressFactory::new()->create([
            'phone' => '254231232132',
        ]);
        $vodacomTransaction = VodacomTransactionFactory::new()->create();
        $transaction = TransactionFactory::new()->create([
            'id' => $this->faker->unique()->randomNumber(),
            'amount' => $this->faker->unique()->randomNumber(),
            'sender' => $address->phone,
            'message' => $address->phone,
            'original_transaction_id' => $vodacomTransaction->id,
            'original_transaction_type' => 'vodacom_transaction',
        ]);
        PaymentHistoryFactory::new()->create(['amount' => $transaction->amount]);
        $response = $this->actingAs($user)->get(sprintf('/api/people/%s/transactions', $person->id));

        $this->assertEquals($person->id, $response['data'][0]['payer_id']);
        $this->assertEquals($transaction->amount, $response['data'][0]['amount']);
    }

    public function test_user_can_delete_a_person()
    {
        /** @var User $user */
        $user = UserFactory::new()->create();
        CityFactory::new()->create();
        CompanyFactory::new()->create();
        CompanyDatabaseFactory::new()->create();
        /** @var Person $person */
        $person = PersonFactory::new()->create();
        $response = $this->actingAs($user)->delete(sprintf('/api/people/%s', $person->id));
        $personListCount = Person::query()->get()->count();
        $this->assertEquals(0, $personListCount);
    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
