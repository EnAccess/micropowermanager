<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Services;

use App\Services\AbstractPaymentProviderCompanyHashService;

class PaystackCompanyHashService extends AbstractPaymentProviderCompanyHashService {
    protected function getUrlPrefix(): string {
        return '/paystack';
    }

    protected function getSaltConfigKey(): string {
        return 'paystack-payment-provider.company_hash_salt';
    }
}
