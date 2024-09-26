<?php

namespace Tests\Feature;

use Inensus\Ticket\Models\TicketCategory;
use Inensus\Ticket\Models\TicketUser;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketTest extends TestCase
{
    use CreateEnvironments;

    public function testUserGetsTicketUserList()
    {
        $this->createTestData();
        $this->createTicketUser();
        $response = $this->actingAs($this->user)->get('/tickets/api/users');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));
    }

    public function testUserCreatesATicketUser()
    {
        $this->createTestData();
        $postData = [
            'username' => 'test',
            'usertag' => 'inensusinensus', // our test user for test trello account
            'outsource' => 0,
        ];
        $response = $this->actingAs($this->user)->post('/tickets/api/users', $postData);
        $response->assertStatus(201);
        $ticketUser = TicketUser::query()->latest('id')->first();
        $this->assertEquals($ticketUser->user_name, $postData['username']);
    }

    public function testUserCreatesATicket()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();

        $postData = [
            'owner_id' => $this->person->id,
            'dueDate' => date('Y-m-d', strtotime('+1 week')),
            'label' => $this->ticketCategory->id,
            'title' => 'title',
            'description' => 'test description',
            'assignedPerson' => $this->ticketUser->extern_id,
            'outsourcing' => 0,
        ];
        $response = $this->actingAs($this->user)->post('/tickets/api/ticket', $postData);
        $response->assertStatus(200);
    }

    public function testUserGetsTicketList()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id);
        $response = $this->actingAs($this->user)->get('/tickets');
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function testUserGetsTicketByTrelloId()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id);
        $trelloId = $this->ticket->ticket_id;
        $response = $this->actingAs($this->user)->get(sprintf('/tickets/%s', $trelloId));
        $response->assertStatus(200);
        $this->assertEquals($trelloId, $response['data']['ticket_id']);
        $this->assertEquals($this->person->id, $response['data']['owner_id']);
    }

    public function testUserClosesATicket()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id);
        $ticketId = $this->ticket->id;
        $trelloId = $this->ticket->ticket_id;
        $response = $this->actingAs($this->user)->delete(sprintf('/tickets/api/ticket/%s', $ticketId));
        $response->assertStatus(200);
        $this->assertEquals($trelloId, $response['data']['id']);
    }

    public function testUserGetsAgentsTicketList()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id, $this->agent->id);
        $response = $this->actingAs($this->user)->get(sprintf('/tickets/api/agents/%s', $this->agent->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function testUserCreatesATicketCategory()
    {
        $this->createTestData();
        $postData = [
            'labelName' => 'test category',
            'labelColor' => 'red',
            'outSource' => 0,
        ];
        $response = $this->actingAs($this->user)->post('/tickets/api/labels', $postData);

        $response->assertStatus(201);
        $ticketCategory = TicketCategory::query()->latest('id')->first();
        $this->assertEquals($ticketCategory->label_name, $postData['labelName']);
    }

    public function testUserGetsTicketCategoryList()
    {
        $this->createTestData();
        $ticketCategoryCount = 5;
        $this->createTicketCategory($ticketCategoryCount);
        $response = $this->actingAs($this->user)->get('/tickets/api/labels');
        $response->assertStatus(200);

        $this->assertEquals($ticketCategoryCount, TicketCategory::query()->count());
    }

    public function testUserGetsTicketListForACustomer()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id);
        $response = $this->actingAs($this->user)->get(sprintf('/tickets/api/tickets/user/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']['data']));
    }

    public function testUserAddsCommentToTicketByTrelloId()
    {
        $this->createTestData();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();
        $this->createTicketBoard();
        $this->createTicketCard();
        $this->createTicket(1, 1, $this->person->id);
        $trelloId = $this->ticket->ticket_id;
        $postData = [
            'cardId' => $trelloId,
            'fullName' => 'test full name',
            'username' => 'test username',
            'comment' => 'test comment',
        ];
        $response = $this->actingAs($this->user)->post('/tickets/api/tickets/comments', $postData);
        $response->assertStatus(200);
    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
