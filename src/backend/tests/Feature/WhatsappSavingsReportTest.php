<?php

namespace Tests\Feature;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\User;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Crypt;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class WhatsappSavingsReportTest extends TestCase {
    use RefreshMultipleDatabases;

    private const string URL = '/api/whatsapp-messaging/messages/savings';

    // 2026-10-01 is a Thursday, so the week grouping spans two Monday buckets.
    private const string PERIOD = '?from=2026-10-01&to=2026-10-07';

    private User $user;

    protected function setUp(): void {
        parent::setUp();
        $this->user = UserFactory::new()->create();

        $this->createWhatsappAttempt('2026-10-01', WhatsappMessage::STATUS_DELIVERED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED, true);
        $this->createWhatsappAttempt('2026-10-01', WhatsappMessage::STATUS_READ, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED, false);
        $this->createWhatsappAttempt('2026-10-03', WhatsappMessage::STATUS_FAILED, MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_SENT, null);
        $this->createWhatsappAttempt('2026-10-06', WhatsappMessage::STATUS_DELIVERED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED, true);
        $this->createWhatsappAttempt('2026-09-20', WhatsappMessage::STATUS_DELIVERED, MpmPlugin::WHATSAPP_MESSAGING, Sms::STATUS_DELIVERED, true);

        $this->createSms('2026-10-03', MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_SENT);
        $this->createSms('2026-10-05', MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_DELIVERED);
        $this->createSms('2026-10-05', MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_FAILED);
        $this->createSms('2026-10-02', MpmPlugin::TEXTBEE_SMS_GATEWAY, Sms::STATUS_DELIVERED, Sms::DIRECTION_INCOMING);
    }

    public function testSeededDataGivesTheHandCalculatedSavings(): void {
        $this->createCredential(0.05, 0.01);

        $savings = $this->actingAs($this->user)->getJson(self::URL.self::PERIOD)->assertOk()->json('data');

        $this->assertSame('USD', $savings['currency']);
        $this->assertSame(2, $savings['sms_only']);
        $this->assertSame(3, $savings['whatsapp_delivered']);
        $this->assertSame(1, $savings['whatsapp_read']);
        $this->assertSame(1, $savings['fell_back']);
        $this->assertSame(2, $savings['whatsapp_billable']);
        // 2 billable messages × 0.01 = 0.02; 3 delivered × 0.05 − 0.02 = 0.13.
        $this->assertEquals(0.02, $savings['whatsapp_cost']);
        $this->assertEquals(0.13, $savings['estimated_saved']);
        $this->assertSame([], $savings['missing']);
    }

    public function testSeriesHasOneEntryPerDayIncludingZeroDays(): void {
        $this->createCredential(0.05, 0.01);

        $series = $this->actingAs($this->user)->getJson(self::URL.self::PERIOD)->json('data.series');

        $this->assertSame(
            ['2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04', '2026-10-05', '2026-10-06', '2026-10-07'],
            array_column($series, 'period'),
        );
        $this->assertSame(2, $series[0]['whatsapp_delivered']);
        $this->assertEquals(0.09, $series[0]['estimated_saved']);
        $this->assertSame(0, $series[1]['whatsapp_delivered']);
        $this->assertSame(0, $series[1]['sms_only']);
        $this->assertEquals(0, $series[1]['estimated_saved']);
        $this->assertSame(1, $series[2]['fell_back']);
        $this->assertSame(1, $series[2]['sms_only']);
    }

    public function testWeekGroupingBucketsByMonday(): void {
        $series = $this->actingAs($this->user)->getJson(self::URL.self::PERIOD.'&group_by=week')->assertOk()->json('data.series');

        $this->assertSame(['2026-09-28', '2026-10-05'], array_column($series, 'period'));
        $this->assertSame(2, $series[0]['whatsapp_delivered']);
        $this->assertSame(1, $series[0]['sms_only']);
        $this->assertSame(1, $series[1]['whatsapp_delivered']);
        $this->assertSame(1, $series[1]['sms_only']);
    }

    public function testMissingCostSettingsReturnCountsAndAMissingList(): void {
        $this->createCredential(null, 0.01);

        $savings = $this->actingAs($this->user)->getJson(self::URL.self::PERIOD)->assertOk()->json('data');

        $this->assertSame(3, $savings['whatsapp_delivered']);
        $this->assertEquals(0.02, $savings['whatsapp_cost']);
        $this->assertNull($savings['estimated_saved']);
        $this->assertSame(['sms_unit_cost'], $savings['missing']);
    }

    public function testWithoutACredentialRowBothCostsAreMissing(): void {
        $savings = $this->actingAs($this->user)->getJson(self::URL.self::PERIOD)->assertOk()->json('data');

        $this->assertSame(2, $savings['sms_only']);
        $this->assertNull($savings['whatsapp_cost']);
        $this->assertNull($savings['estimated_saved']);
        $this->assertSame(['sms_unit_cost', 'whatsapp_utility_cost'], $savings['missing']);
    }

    public function testInvalidQueriesAreRejected(): void {
        $this->actingAs($this->user)->getJson(self::URL.'?to=2026-10-07')
            ->assertStatus(422)
            ->assertJsonValidationErrors('from');
        $this->actingAs($this->user)->getJson(self::URL.self::PERIOD.'&group_by=month')
            ->assertStatus(422)
            ->assertJsonValidationErrors('group_by');
        $this->actingAs($this->user)->getJson(self::URL.'?from=2025-01-01&to=2026-10-07')
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');
    }

    public function testSavingsRequiresLogin(): void {
        $this->getJson(self::URL.self::PERIOD)->assertUnauthorized();
    }

    private function createCredential(?float $smsUnitCost, ?float $whatsappUtilityCost): void {
        WhatsappCredential::query()->create([
            'webhook_verify_token' => Crypt::encryptString('verify-token'),
            'sms_unit_cost' => $smsUnitCost,
            'whatsapp_utility_cost' => $whatsappUtilityCost,
            'cost_currency' => 'USD',
        ]);
    }

    private function createSms(string $day, int $gatewayId, int $status, int $direction = Sms::DIRECTION_OUTGOING): Sms {
        return Sms::query()->create([
            'receiver' => '+255700000000',
            'body' => 'Payment received',
            'direction' => $direction,
            'status' => $status,
            'gateway_id' => $gatewayId,
            'attempts' => 1,
            'created_at' => $day.' 10:00:00',
        ]);
    }

    private function createWhatsappAttempt(string $day, string $whatsappStatus, int $gatewayId, int $smsStatus, ?bool $billable): void {
        $sms = $this->createSms($day, $gatewayId, $smsStatus);

        WhatsappMessage::query()->create([
            'sms_id' => $sms->id,
            'wamid' => 'wamid.'.$sms->id,
            'message_type' => WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
            'template_name' => 'mpm_payment_receipt_meter',
            'template_language' => 'en',
            'status' => $whatsappStatus,
            'billable' => $billable,
            'created_at' => $day.' 10:00:00',
        ]);
    }
}
