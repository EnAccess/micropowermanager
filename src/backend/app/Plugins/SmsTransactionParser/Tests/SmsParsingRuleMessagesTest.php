<?php

namespace App\Plugins\SmsTransactionParser\Tests;

use App\Plugins\SmsTransactionParser\Models\SmsParsingRule;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use Database\Factories\UserFactory;
use Tests\TestCase;

class SmsParsingRuleMessagesTest extends TestCase {
    public function testReturnsTransactionsForParsingRule(): void {
        $user = UserFactory::new()->create();
        $user->syncRoles('admin');

        $rule = SmsParsingRule::query()->create([
            'provider_name' => 'Vodacom',
            'template' => 'test',
            'pattern' => '/test/',
            'enabled' => true,
        ]);

        SmsTransaction::query()->create([
            'provider_name' => 'Vodacom',
            'transaction_reference' => 'REF001',
            'amount' => 100.00,
            'sender_phone' => '258841234567',
            'device_serial' => 'METER001',
            'raw_message' => 'test message 1',
            'status' => SmsTransaction::STATUS_SUCCESS,
        ]);

        SmsTransaction::query()->create([
            'provider_name' => 'Vodacom',
            'transaction_reference' => 'REF002',
            'amount' => 200.00,
            'sender_phone' => '258841234567',
            'device_serial' => 'METER002',
            'raw_message' => 'test message 2',
            'status' => SmsTransaction::STATUS_SUCCESS,
        ]);

        SmsTransaction::query()->create([
            'provider_name' => 'Movitel',
            'transaction_reference' => 'REF003',
            'amount' => 300.00,
            'sender_phone' => '258841234567',
            'device_serial' => 'METER003',
            'raw_message' => 'different provider message',
            'status' => SmsTransaction::STATUS_SUCCESS,
        ]);

        $response = $this->actingAs($user)
            ->get("/api/sms-transaction-parser/parsing-rules/{$rule->id}/messages");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $refs = array_column($data, 'transaction_reference');
        sort($refs);
        $this->assertEquals(['REF001', 'REF002'], $refs);
    }

    public function testReturnsEmptyForRuleWithNoTransactions(): void {
        $user = UserFactory::new()->create();
        $user->syncRoles('admin');

        $rule = SmsParsingRule::query()->create([
            'provider_name' => 'NewProvider',
            'template' => 'test',
            'pattern' => '/test/',
            'enabled' => true,
        ]);

        $response = $this->actingAs($user)
            ->get("/api/sms-transaction-parser/parsing-rules/{$rule->id}/messages");

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function testReturnsPaginatedResults(): void {
        $user = UserFactory::new()->create();
        $user->syncRoles('admin');

        $rule = SmsParsingRule::query()->create([
            'provider_name' => 'Vodacom',
            'template' => 'test',
            'pattern' => '/test/',
            'enabled' => true,
        ]);

        for ($i = 1; $i <= 20; ++$i) {
            SmsTransaction::query()->create([
                'provider_name' => 'Vodacom',
                'transaction_reference' => "REF{$i}",
                'amount' => $i * 10.0,
                'sender_phone' => '258841234567',
                'device_serial' => "METER{$i}",
                'raw_message' => "message {$i}",
                'status' => SmsTransaction::STATUS_SUCCESS,
            ]);
        }

        $response = $this->actingAs($user)
            ->get("/api/sms-transaction-parser/parsing-rules/{$rule->id}/messages");

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertEquals(20, $response->json('total'));
        $this->assertEquals(2, $response->json('last_page'));
    }
}
