<?php

namespace Tests\Feature;

use App\Models\Ticket\TicketCategory;
use Illuminate\Contracts\Auth\Authenticatable;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsTicketUserList(): void {
        $this->createTestData();
        $this->createTicketUser();
        $response = $this->actingAs($this->user)->get('/api/tickets/users');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
    }

    public function testUserCreatesATicket(): void {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();

        $postData = [
            'owner_id' => $this->person->id,
            'dueDate' => date('Y-m-d', strtotime('+1 week')),
            'label' => $this->ticketCategory->id,
            'title' => 'title',
            'description' => 'test description',
            'assignedPerson' => $this->ticketUser->extern_id,
            'outsourcing' => 0,
        ];
        $response = $this->actingAs($this->user)->post('/api/tickets/ticket', $postData);
        $response->assertStatus(200);
    }

    public function testUserGetsTicketList(): void {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicket(1, 1, $this->person->id);
        $response = $this->actingAs($this->user)->get('/api/tickets/ticket');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function testUserClosesATicket(): void {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicket(1, 1, $this->person->id);
        $ticketId = $this->ticket->id;
        $response = $this->actingAs($this->user)->delete(sprintf('/api/tickets/ticket/%s', $ticketId));
        $response->assertStatus(200);
    }

    public function testUserGetsAgentsTicketList(): void {
        $this->createTestData();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicket(1, 1, $this->person->id, $this->agent->id);
        $response = $this->actingAs($this->user)->get(sprintf('/api/tickets/agents/%s', $this->agent->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function testUserCreatesATicketCategory(): void {
        $this->createTestData();
        $postData = [
            'labelName' => 'test category',
            'labelColor' => 'red',
            'outSource' => 0,
        ];
        $response = $this->actingAs($this->user)->post('/api/tickets/labels', $postData);

        $response->assertStatus(201);
        $ticketCategory = TicketCategory::query()->latest('id')->first();
        $this->assertEquals($ticketCategory->label_name, $postData['labelName']);
    }

    public function testUserGetsTicketCategoryList(): void {
        $this->createTestData();
        $ticketCategoryCount = 5;
        $this->createTicketCategory($ticketCategoryCount);
        $response = $this->actingAs($this->user)->get('/api/tickets/labels');
        $response->assertStatus(200);

        $this->assertEquals($ticketCategoryCount, TicketCategory::query()->count());
    }

    public function testUserGetsTicketListForACustomer(): void {
        $this->createTestData();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicket(1, 1, $this->person->id);
        $response = $this->actingAs($this->user)->get(sprintf('/api/tickets/user/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function actingAs(Authenticatable $user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
