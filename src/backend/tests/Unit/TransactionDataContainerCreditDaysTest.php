<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\TransactionDataContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TransactionDataContainerCreditDaysTest extends TestCase {
    /**
     * @return array<string, array{float, float, float, float}>
     */
    public static function periodPayments(): array {
        return [
            'one weekly installment' => [17.0, 17.0, 7.0, 7.0],
            'one monthly installment' => [200.0, 200.0, 30.0, 30.0],
            'one monthly installment, 31-day month' => [61.0, 61.0, 31.0, 31.0],
            'half an installment rounds up' => [100.0, 200.0, 30.0, 15.0],
            'a third of an installment rounds up' => [100.0, 300.0, 30.0, 10.0],
            'energy service day price' => [250.0, 100.0, 1.0, 3.0],
        ];
    }

    #[DataProvider('periodPayments')]
    public function testItVendsWholeDaysWithoutFloatDrift(float $amount, float $installmentCost, float $dayDifference, float $expectedDays): void {
        $container = new TransactionDataContainer();
        $container->amount = $amount;
        $container->installmentCost = $installmentCost;
        $container->dayDifferenceBetweenTwoInstallments = $dayDifference;

        $this->assertSame($expectedDays, $container->creditDays());
    }

    public function testAnExactPeriodIsNeverBumpedUpADay(): void {
        foreach ([7.0, 14.0, 28.0, 30.0, 31.0] as $period) {
            foreach (range(1, 500) as $amount) {
                $container = new TransactionDataContainer();
                $container->amount = (float) $amount;
                $container->installmentCost = (float) $amount;
                $container->dayDifferenceBetweenTwoInstallments = $period;

                $this->assertSame($period, $container->creditDays(), "{$amount} over {$period} days");
            }
        }
    }
}
