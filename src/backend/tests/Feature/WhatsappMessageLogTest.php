<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Database\Factories\UserFactory;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappMessageLogTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string URL = '/api/whatsapp-messaging/messages';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();

        $this->createAttempt('+255700000001', WhatsappMessage::STATUS_DELIVERED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED);
        $this->createAttempt('+255700000002', WhatsappMessage::STATUS_READ, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED);
        $this->createAttempt('+255700000003', WhatsappMessage::STATUS_FAILED, MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_SENT, 131026);
        $this->createAttempt('+255700000004', WhatsappMessage::STATUS_FAILED, MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_FAILED, 131030);
        $this->createAttempt('+255700000005', WhatsappMessage::STATUS_ACCEPTED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_SENT);
    }

    public function testEachOutcomeFilterReturnsItsMessages(): void {
        $expectedReceivers = [
            WhatsappMessage::OUTCOME_DELIVERED_WHATSAPP => ['+255700000002', '+255700000001'],
            WhatsappMessage::OUTCOME_FELL_BACK => ['+255700000003'],
            WhatsappMessage::OUTCOME_FAILED => ['+255700000004'],
            WhatsappMessage::OUTCOME_PENDING => ['+255700000005'],
        ];

        foreach ($expectedReceivers as $outcome => $receivers) {
            $response = $this->actingAs($this->user)->getJson(self::URL.'?outcome='.$outcome);

            $response->assertOk();
            $this->assertSame($receivers, array_column($response->json('data'), 'receiver'), $outcome);
            foreach ($response->json('data') as $item) {
                $this->assertSame($outcome, $item['outcome']);
            }
        }
    }

    public function testFellBackMessageShowsSmsAsFinalChannel(): void {
        $response = $this->actingAs($this->user)->getJson(self::URL.'?outcome=fell_back');

        $response->assertJsonPath('data.0.whatsapp_status', WhatsappMessage::STATUS_FAILED);
        $response->assertJsonPath('data.0.error_code', 131026);
        $response->assertJsonPath('data.0.final_channel', 'sms');
    }

    public function testSummaryMatchesTheList(): void {
        $summary = $this->actingAs($this->user)->getJson(self::URL.'/summary')->assertOk()->json('data');

        $this->assertSame(5, $summary['total']);
        $this->assertSame(2, $summary['delivered_whatsapp']);
        $this->assertSame(1, $summary['read']);
        $this->assertSame(1, $summary['fell_back']);
        $this->assertSame(1, $summary['failed']);
        $this->assertEquals(0.4, $summary['delivery_rate']);
        $this->assertEquals(0.2, $summary['read_rate']);
        $this->assertCount(2, $summary['top_errors']);

        $summarisedOutcomes = [WhatsappMessage::OUTCOME_DELIVERED_WHATSAPP, WhatsappMessage::OUTCOME_FELL_BACK, WhatsappMessage::OUTCOME_FAILED];
        foreach ($summarisedOutcomes as $outcome) {
            $listed = $this->actingAs($this->user)->getJson(self::URL.'?outcome='.$outcome)->json('meta.total');
            $this->assertSame($summary[$outcome], $listed, $outcome);
        }
        $this->assertSame($summary['total'], $this->actingAs($this->user)->getJson(self::URL)->json('meta.total'));
    }

    public function testSummaryOfAnEmptyPeriodHasNoRates(): void {
        $summary = $this->actingAs($this->user)->getJson(self::URL.'/summary?from=2001-01-01&to=2001-01-31')->assertOk()->json('data');

        $this->assertSame(0, $summary['total']);
        $this->assertNull($summary['delivery_rate']);
        $this->assertNull($summary['read_rate']);
        $this->assertSame([], $summary['top_errors']);
    }

    public function testPerPageAboveTheLimitIsRejected(): void {
        $this->actingAs($this->user)->getJson(self::URL.'?per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function testShowReturnsTheSmsBodyAndError(): void {
        $message = WhatsappMessage::query()->where('error_code', 131030)->sole();

        $response = $this->actingAs($this->user)->getJson(self::URL.'/'.$message->id);

        $response->assertOk();
        $response->assertJsonPath('data.body', 'Body for +255700000004');
        $response->assertJsonPath('data.error_message', 'Error 131030');
        $response->assertJsonPath('data.outcome', WhatsappMessage::OUTCOME_FAILED);
    }

    public function testPhoneFilterMatchesDigits(): void {
        $response = $this->actingAs($this->user)->getJson(self::URL.'?phone='.urlencode('+255 700 000 003'));

        $this->assertSame(['+255700000003'], array_column($response->json('data'), 'receiver'));
    }

    public function testLogRequiresLogin(): void {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    private function createAttempt(string $receiver, string $whatsappStatus, int $gatewayId, int $smsStatus, ?int $errorCode = null): void {
        $sms = Sms::query()->create([
            'receiver' => $receiver,
            'body' => 'Body for '.$receiver,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => $smsStatus,
            'gateway_id' => $gatewayId,
            'attempts' => 1,
        ]);

        WhatsappMessage::query()->create([
            'sms_id' => $sms->id,
            'wamid' => 'wamid.'.$receiver,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'mpm_payment_receipt_meter',
            'template_language' => 'en',
            'status' => $whatsappStatus,
            'error_code' => $errorCode,
            'error_message' => $errorCode === null ? null : 'Error '.$errorCode,
            'failed_at' => $errorCode === null ? null : now(),
        ]);
    }
}
