<?php

namespace App\Plugins\SparkMeter\Tests\Unit;

use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Helpers\ResultStatusChecker;
use App\Plugins\SparkMeter\Helpers\SmTableEncryption;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Plugins\SparkMeter\Models\SmSalesAccount;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Services\SmSalesAccoutService;
use App\Plugins\SparkMeter\Services\SmSyncActionService;
use App\Plugins\SparkMeter\Services\SmSyncSettingService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SmSalesAccoutServiceIssuePaymentTest extends TestCase {
    private function createSite(): SmSite {
        return SmSite::query()->create([
            'site_id' => 'site-1',
            'mpm_mini_grid_id' => 1,
            'thundercloud_url' => 'https://site-1.sparkmeter.cloud/api/v0',
            'thundercloud_token' => 'a-token',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);
    }

    private function createSalesAccount(): SmSalesAccount {
        return SmSalesAccount::query()->create([
            'sales_account_id' => 'sa-123',
            'site_id' => 'site-1',
            'name' => 'Cash Account',
            'account_type' => 'cash',
            'active' => true,
            'credit' => 100,
            'markup' => 0,
        ]);
    }

    /**
     * @param array<int, Response|\Throwable> $responses
     */
    private function makeService(array $responses): SmSalesAccoutService {
        $requests = new SparkMeterApiRequests(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            new ResultStatusChecker(),
            new SmSite(),
            new SmCredential(),
        );

        return new SmSalesAccoutService(
            $requests,
            new SmTableEncryption(),
            new SmSalesAccount(),
            new SmSite(),
            resolve(SmSyncSettingService::class),
            resolve(SmSyncActionService::class),
        );
    }

    public function testIssuePaymentPostsToTheSalesAccountPaymentEndpoint(): void {
        $this->createSite();
        $salesAccount = $this->createSalesAccount();
        $service = $this->makeService([
            new Response(200, [], (string) json_encode(['error' => false, 'bonus_transaction_id' => null])),
        ]);

        $result = $service->issuePayment($salesAccount, ['amount' => '10']);

        $this->assertSame(false, $result['error']);
    }

    public function testIssuePaymentWrapsTransportFailuresInSparkAPIResponseException(): void {
        $this->createSite();
        $salesAccount = $this->createSalesAccount();
        $service = $this->makeService([
            new ConnectException('Connection refused', new Request('POST', '/sales-accounts/sa-123/payment')),
        ]);

        $this->expectException(SparkAPIResponseException::class);
        $service->issuePayment($salesAccount, ['amount' => '10']);
    }
}
