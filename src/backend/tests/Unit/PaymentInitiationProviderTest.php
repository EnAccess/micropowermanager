<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\PaymentInitiationProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentInitiationProviderTest extends TestCase {
    /**
     * @return array<string, array{0: mixed, 1: PaymentInitiationProvider|null}>
     */
    public static function requestValues(): array {
        return [
            'absent means cash' => [null, PaymentInitiationProvider::Cash],
            'slug' => ['paystack_payment_provider', PaymentInitiationProvider::Paystack],
            'cash slug' => ['cash', PaymentInitiationProvider::Cash],
            'legacy cash id' => [0, PaymentInitiationProvider::Cash],
            'legacy id' => [19, PaymentInitiationProvider::VodacomMz],
            'legacy id as string' => ['25', PaymentInitiationProvider::Paystack],
            'legacy third party id' => [-1, PaymentInitiationProvider::ThirdParty],
            'unknown legacy id' => [999, null],
            'inbound only provider' => ['swifta_payment_provider', null],
            'unknown slug' => ['unknown', null],
        ];
    }

    #[DataProvider('requestValues')]
    public function testItParsesRequestValues(mixed $value, ?PaymentInitiationProvider $expected): void {
        $this->assertSame($expected, PaymentInitiationProvider::tryFromRequestValue($value));
    }

    public function testItRejectsAnUnknownProvider(): void {
        $this->expectException(\InvalidArgumentException::class);

        PaymentInitiationProvider::fromRequestValue(999);
    }

    public function testEveryProviderKeepsItsLegacyId(): void {
        foreach (PaymentInitiationProvider::cases() as $provider) {
            $this->assertSame($provider, PaymentInitiationProvider::fromRequestValue($provider->legacyId()));
        }
    }
}
