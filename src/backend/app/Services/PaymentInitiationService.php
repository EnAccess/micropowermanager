<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MpmPlugin;
use App\Enums\PaymentInitiationProvider;
use App\Exceptions\PaymentProviderNotEnabledException;
use App\Models\Plugins;
use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Container\Container;

class PaymentInitiationService {
    public function __construct(
        private PluginsService $pluginsService,
        private Container $container,
    ) {}

    /**
     * Initialize a payment with any enabled provider.
     *
     * @param string      $sender     Phone number of the payer
     * @param string      $message    Transaction routing key (device serial or entity ID)
     * @param string      $type       Transaction type (e.g. 'deferred_payment', 'energy')
     * @param int         $customerId Person ID of the customer
     * @param string|null $serialId   Device serial, if applicable
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiate(
        PaymentInitiationProvider $provider,
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $mpmPlugin = $provider->mpmPlugin();

        if ($mpmPlugin instanceof MpmPlugin && !$this->pluginsService->isPluginActive($mpmPlugin)) {
            throw new PaymentProviderNotEnabledException("Payment provider {$provider->name} is not enabled.");
        }

        $initiator = $this->container->make($provider->initiatorClass());

        return $initiator->initiatePayment(
            $amount,
            $sender,
            $message,
            $type,
            $customerId,
            $serialId,
        );
    }

    /**
     * Active payment provider plugins that can initiate a payment.
     * Inbound-only providers (e.g. Swifta, MeSomb) are excluded.
     *
     * @return list<PaymentInitiationProvider>
     */
    public function paymentProviders(): array {
        $providers = [];
        foreach ($this->pluginsService->getActivePaymentProviders() as $plugin) {
            $provider = PaymentInitiationProvider::tryFrom($plugin->mpm_plugin->value);
            if ($provider instanceof PaymentInitiationProvider) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }
}
