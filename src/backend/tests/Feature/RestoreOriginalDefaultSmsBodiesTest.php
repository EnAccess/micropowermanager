<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\CreateEnvironments;
use Tests\TestCase;

class RestoreOriginalDefaultSmsBodiesTest extends TestCase {
    use CreateEnvironments;

    private const string IMPROVED_REMINDER = 'The next payment for your [appliance_type_name] of [remaining] is due on [due_date].';
    private const string ORIGINAL_REMINDER = 'the next rate of  [appliance_type_name] ( . [remaining] . ) is due on [due_date]';

    public function testFreshTenantsHaveTheOriginalWording(): void {
        $this->assertSame(self::ORIGINAL_REMINDER, $this->smsBody('ApplianceRateReminder')->body);
        $this->assertSame(self::ORIGINAL_REMINDER, $this->smsBody('ApplianceRateReminder')->place_holder);
    }

    public function testImprovedDefaultIsRestored(): void {
        $this->setBody('ApplianceRateReminder', self::IMPROVED_REMINDER);

        $this->runMigration();

        $this->assertSame(self::ORIGINAL_REMINDER, $this->smsBody('ApplianceRateReminder')->body);
        $this->assertSame(self::ORIGINAL_REMINDER, $this->smsBody('ApplianceRateReminder')->place_holder);
    }

    public function testCustomizedWordingIsKept(): void {
        $customized = 'Pay [remaining] for your [appliance_type_name] by [due_date].';
        $this->setBody('ApplianceRateReminder', $customized);

        $this->runMigration();

        $this->assertSame($customized, $this->smsBody('ApplianceRateReminder')->body);
        $this->assertSame(self::ORIGINAL_REMINDER, $this->smsBody('ApplianceRateReminder')->place_holder);
    }

    private function smsBody(string $reference): object {
        return DB::connection('tenant')->table('sms_bodies')->where('reference', $reference)->first();
    }

    private function setBody(string $reference, string $body): void {
        DB::connection('tenant')->table('sms_bodies')->where('reference', $reference)->update(['body' => $body, 'place_holder' => $body]);
    }

    private function runMigration(): void {
        $migration = require database_path('migrations/tenant/2026_11_02_000000_restore_original_default_sms_bodies.php');
        $migration->up();
    }
}
