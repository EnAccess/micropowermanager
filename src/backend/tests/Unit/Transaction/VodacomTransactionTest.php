<?php

namespace Tests\Unit\Transaction;

use App\Http\Middleware\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use MPM\Transaction\Provider\VodacomTransactionProvider;
use Tests\TestCase;

class VodacomTransactionTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var Response
     */
    public function testTransactionSentFromUnknownIpAddress(): void
    {
        $request = Request::create(URL::route('vodacomTransaction'), 'POST', [], [], [],
            ['REMOTE_ADDR' => '127.0.0.2']);
        $middleWare = new Transaction();

        $response = $middleWare->handle($request, function () {
        });

        self::assertEquals(401, $response->status());
    }

    public function testTransactionSentFromWhiteListedIpAddress(): void
    {
        $request = Request::create(URL::route('vodacomTransaction'), 'POST', [], [], [],
            ['REMOTE_ADDR' => config('services.vodacom.ips')[0]]);

        $middleWare = new Transaction();

        $middleWare->handle($request, function ($x) {
            $this->assertInstanceOf(VodacomTransactionProvider::class, $x->attributes->get('transactionProcessor'));
        });
    }
}
