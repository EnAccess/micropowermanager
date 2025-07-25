<?php

namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SmsLoadBalancer extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;
    public int $tries = 250;
    public $gateways;

    public array $smsBody;

    public function __construct(array $smsBody) {
        $this->smsBody = $smsBody;
        parent::__construct(get_class($this));
    }

    public function executeJob(): void {
        Redis::throttle('smsgateway')->allow(10)->every(1)->block(1)->then(
            function () {
                $fireBaseResult = $this->sendSms($this->smsBody);
                Log::debug('smsgateway', ['data' => $this->smsBody, 'firebase' => $fireBaseResult]);
            },
            function () {
                $this->release(1);
            }
        );
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function sendSms($data): string {
        /** @var Collection<int, mixed> $smsCollection */
        $smsCollection = collect($data);
        $smsCollection = $smsCollection->chunk(3);
        $httpClient = new Client();
        $request = $httpClient->post(
            $smsCollection[1]['setting']['url'],
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'key='.$smsCollection[1]['setting']['key'],
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'data' => $smsCollection[0],
                    'to' => $smsCollection[1]['setting']['token'],
                ],
            ]
        );

        return (string) $request->getBody();
    }
}
