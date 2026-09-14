<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Services;

use App\Services\AbstractPaymentProviderCompanyHashService;

class FlutterwaveCompanyHashService extends AbstractPaymentProviderCompanyHashService {
    protected function getUrlPrefix(): string {
        return '/flutterwave';
    }

    protected function getSaltConfigKey(): string {
        return 'flutterwave-payment-provider.company_hash_salt';
    }
}
