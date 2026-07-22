<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Sms\BodyParsers\TokenConfirmationSHS;
use Tests\TestCase;

class TokenConfirmationSHSTest extends TestCase {
    private const TEMPLATE = 'Dear [name] [surname], your transaction is confirmed. Device Serial: [device_serial]. Token: [token], Duration: [duration] [unit]. Amount: [amount].';

    public function testIncludesAmountFromTransaction(): void {
        $body = $this->parse(amount: 1500, message: 'SERIAL123');

        $this->assertStringContainsString('Amount: 1500.', $body);
    }

    public function testRendersAllVariables(): void {
        $body = $this->parse(amount: 2000, message: 'SHS-9');

        $this->assertSame(
            'Dear John Doe, your transaction is confirmed. Device Serial: SHS-9. Token: 123456789012, Duration: 3 days. Amount: 2000.',
            $body,
        );
    }

    private function parse(float $amount, string $message): string {
        $person = new Person(['name' => 'John', 'surname' => 'Doe']);

        $device = new Device();
        $device->setRelation('person', $person);

        $transaction = new Transaction(['amount' => $amount, 'message' => $message]);
        $transaction->setRelation('device', $device);

        $token = new Token([
            'token' => '123456789012',
            'token_type' => Token::TYPE_TIME,
            'token_unit' => Token::UNIT_DAYS,
            'token_amount' => 3,
        ]);

        return (new TokenConfirmationSHS($transaction, $token))->parseSms(self::TEMPLATE);
    }
}
