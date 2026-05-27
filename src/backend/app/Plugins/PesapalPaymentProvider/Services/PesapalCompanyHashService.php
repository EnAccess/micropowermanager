<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Services\AbstractPaymentProviderCompanyHashService;

class PesapalCompanyHashService extends AbstractPaymentProviderCompanyHashService {
    protected function getUrlPrefix(): string {
        return '/pesapal';
    }

    protected function getSaltConfigKey(): string {
        return 'pesapal-payment-provider.company_hash_salt';
    }
}
