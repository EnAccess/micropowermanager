<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Listeners\SmsListener;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Models\SteamaSmsFeedbackWord;
use App\Plugins\SteamaMeter\Sms\Senders\SteamaSmsConfig;
use App\Plugins\SteamaMeter\Sms\SteamaSmsTypes;
use App\Services\SmsService;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class SmsNotifyTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private function createCustomerWithPhone(string $phone): SteamaCustomer {
        $person = Person::query()->create([
            'name' => 'Jane',
            'surname' => 'Doe',
            'is_customer' => 1,
        ]);
        $person->addresses()->create([
            'phone' => $phone,
            'email' => 'jane@example.com',
            'street' => 'Main St',
            'is_primary' => 1,
        ]);

        return SteamaCustomer::query()->create([
            'site_id' => 1,
            'user_type_id' => 1,
            'customer_id' => 7001,
            'mpm_customer_id' => $person->id,
            'account_balance' => 5,
            'low_balance_warning' => 10,
        ]);
    }

    public function testSendsBalanceFeedbackWithTheCustomerModelWhenFeedbackWordMatches(): void {
        $customer = $this->createCustomerWithPhone('+255700000001');
        SteamaSmsFeedbackWord::query()->create(['meter_balance' => 'balance']);

        $this->mock(SmsService::class, function (MockInterface $mock) use ($customer): void {
            $mock->shouldReceive('sendSms')->once()->withArgs(
                fn ($data, $type, $config): bool => $data instanceof SteamaCustomer
                    && $data->id === $customer->id
                    && $type === SteamaSmsTypes::BALANCE_FEEDBACK
                    && $config === SteamaSmsConfig::class
            );
        });

        app(SmsListener::class)->onSmsStored('+255700000001', 'Your BALANCE is low');
    }

    public function testDoesNotSendWhenMessageDoesNotContainTheFeedbackWord(): void {
        $this->createCustomerWithPhone('+255700000002');
        SteamaSmsFeedbackWord::query()->create(['meter_balance' => 'balance']);

        $this->mock(SmsService::class, fn (MockInterface $mock) => $mock->shouldReceive('sendSms')->never());

        app(SmsListener::class)->onSmsStored('+255700000002', 'unrelated message');
    }

    public function testDoesNotSendWhenNoFeedbackWordIsConfigured(): void {
        $this->createCustomerWithPhone('+255700000003');

        $this->mock(SmsService::class, fn (MockInterface $mock) => $mock->shouldReceive('sendSms')->never());

        app(SmsListener::class)->onSmsStored('+255700000003', 'balance');
    }

    public function testIgnoresSmsFromAnUnknownSender(): void {
        $this->mock(SmsService::class, fn (MockInterface $mock) => $mock->shouldReceive('sendSms')->never());

        app(SmsListener::class)->onSmsStored('+255999999999', 'balance');
    }
}
