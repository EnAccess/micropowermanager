<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentAppTicketsTest extends TestCase {
    use CreateEnvironments;

    public function testAgentGetsTicketList() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $ticketCount = 1;
        $this->createTicket($ticketCount, 1, $this->person->id, $this->agent->id);
        $response = $this->actingAs($this->agent)->get('/api/app/agents/ticket');
        $response->assertStatus(200);
        $this->assertEquals($ticketCount, count($response['data']['data']));
    }

    public function testAgentGetsTicketById() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $ticketCount = 1;
        $this->createTicket($ticketCount, 1, $this->person->id, $this->agent->id);

        $response = $this->actingAs($this->agent)->get(sprintf('/api/app/agents/ticket/%s', $this->ticket->id));
        $response->assertStatus(200);
        $this->assertEquals($this->ticket->id, $response['data']['id']);
        $this->assertEquals($this->agent->id, $response['data']['creator_id']);
    }

    public function testAgentGetsTicketCustomerId() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createTicketCategory();
        $this->createTicketUser();
        $ticketCount = 1;
        $this->createTicket($ticketCount, 1, $this->person->id, $this->agent->id);
        $response = $this->actingAs($this->agent)->get(sprintf(
            '/api/app/agents/ticket/customer/%s',
            $this->person->id
        ));
        $response->assertStatus(200);
        $this->assertEquals($this->ticket->id, $response['data']['data'][0]['id']);
        $this->assertEquals($this->agent->id, $response['data']['data'][0]['creator_id']);
    }

    public function testAgentCreatesATicket() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $this->createTicketCategory();
        $this->createTicketUser();

        $postData = [
            'owner_id' => $this->person->id,
            'due_date' => date('Y-m-d', strtotime('+1 week')),
            'label' => $this->ticketCategory->id,
            'title' => 'title',
            'description' => 'test description',
            'assignedId' => $this->ticketUser->extern_id,
            'outsourcing' => 0,
        ];
        $response = $this->actingAs($this->agent)->post('/api/app/agents/ticket', $postData);
        $response->assertStatus(200);
        $this->assertEquals($this->agent->id, $response['data'][0]['creator_id']);
        $this->assertEquals('agent', $response['data'][0]['creator_type']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
