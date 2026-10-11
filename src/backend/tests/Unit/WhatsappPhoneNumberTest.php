<?php

namespace Tests\Unit;

use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use Tests\TestCase;

class WhatsappPhoneNumberTest extends TestCase {
    public function testItConvertsE164ToWaId(): void {
        $this->assertSame('255712345678', WhatsappPhoneNumber::toWaId('+255712345678'));
        $this->assertSame('255712345678', WhatsappPhoneNumber::toWaId('+255 712 345 678'));
    }

    public function testItConvertsWaIdToE164(): void {
        $this->assertSame('+255712345678', WhatsappPhoneNumber::fromWaId('255712345678'));
    }
}
