<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Maps third-party API request paths to the resolver that identifies the
 * company behind the request, and runs it. A request belongs to a resolver
 * when its path starts with the resolver's registered prefix (case-insensitive).
 */
class ThirdPartyApiResolverService {
    public const VIBER_API = 'api/viber-messaging/webhook';
    public const TEST_API = 'api/testApi';
    public const WAVE_MONEY_API = 'api/wave-money/wave-money-transaction';
    public const ANDROID_GATEWAY_CALLBACK_API = 'api/sms-android-callback';
    public const SWIFTA_PAYMENT_API = 'api/swifta/';
    public const REPORT_DOWNLOADING_API = 'api/report-downloading';
    public const DATA_EXPORTING_API = 'api/export';
    public const ODYSSEY_PAYMENTS_API = 'api/odyssey';
    public const AFRICAS_TALKING_API = 'api/africas-talking/callback';
    public const VODACOM_MZ_PAYMENT_PROVIDER = 'api/vodacom_mz/transactions/';
    public const PAYSTACK_API = 'api/paystack/';
    public const PESAPAL_API = 'api/pesapal/';
    public const ECREEE_METER_DATA_API = 'api/ecreee-e-tender/ecreee-meter-data';
    public const TEXTBEE_SMS_GATEWAY_API = 'api/textbee-sms-gateway/callback';

    /**
     * @var array<string, class-string<IApiResolver>>
     */
    private const array RESOLVERS = [
        self::TEST_API => TestApiResolver::class,
        self::VIBER_API => ViberMessagingApiResolver::class,
        self::WAVE_MONEY_API => WaveMoneyApiResolver::class,
        self::ANDROID_GATEWAY_CALLBACK_API => AndroidGatewayCallbackApiResolver::class,
        self::SWIFTA_PAYMENT_API => SwiftaPaymentApiResolver::class,
        self::REPORT_DOWNLOADING_API => DownloadingReportsResolver::class,
        self::DATA_EXPORTING_API => DataExportResolver::class,
        self::AFRICAS_TALKING_API => AfricasTalkingApiResolver::class,
        self::VODACOM_MZ_PAYMENT_PROVIDER => VodacomMzApiResolver::class,
        self::ODYSSEY_PAYMENTS_API => OdysseyPaymentApiResolver::class,
        self::PAYSTACK_API => PaystackApiResolver::class,
        self::PESAPAL_API => PesapalApiResolver::class,
        self::ECREEE_METER_DATA_API => EcreeeMeterDataApiResolver::class,
        self::TEXTBEE_SMS_GATEWAY_API => TextbeeSmsGatewayApiResolver::class,
    ];

    public function matches(string $requestPath): bool {
        return $this->resolverClassFor($requestPath) !== null;
    }

    public function resolve(Request $request): int {
        $resolverClass = $this->resolverClassFor($request->path());
        if ($resolverClass === null) {
            throw ValidationException::withMessages(['path' => 'No api resolver registered for '.$request->path()]);
        }

        /** @var IApiResolver $resolver */
        $resolver = app()->make($resolverClass);
        $companyId = $resolver->resolveCompanyId($request);
        $request->attributes->add(['companyId' => $companyId]);

        return $companyId;
    }

    /**
     * @return class-string<IApiResolver>|null
     */
    private function resolverClassFor(string $requestPath): ?string {
        foreach (self::RESOLVERS as $apiPath => $resolverClass) {
            if (Str::startsWith(Str::lower($requestPath), Str::lower($apiPath))) {
                return $resolverClass;
            }
        }

        return null;
    }
}
