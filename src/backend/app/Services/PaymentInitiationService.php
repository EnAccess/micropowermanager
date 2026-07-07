<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PaymentInitiationProvider;
use App\Models\Plugins;
use App\Models\Transaction\Transaction;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class PaymentInitiationService {
    public function __construct(
        private PluginsService $pluginsService,
        private MpmPluginService $mpmPluginService,
        private Container $container,
    ) {}

    /**
     * Initialize a payment with any enabled provider.
     *
     * @param int         $providerId MpmPlugin constant (0 = cash)
     * @param string      $sender     Phone number of the payer
     * @param string      $message    Transaction routing key (device serial or entity ID)
     * @param string      $type       Transaction type (e.g. 'deferred_payment', 'energy')
     * @param int         $customerId Person ID of the customer
     * @param string|null $serialId   Device serial, if applicable
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiate(
        int $providerId,
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $provider = PaymentInitiationProvider::tryFrom($providerId)
            ?? throw new \InvalidArgumentException("Unsupported payment provider ID: {$providerId}");

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
     * @return Collection<int, array{id: int, name: string}>
     */
    public function paymentProviders(): Collection {
        return $this->pluginsService->getActivePaymentProviders()
            ->filter(fn (Plugins $plugin): bool => PaymentInitiationProvider::tryFrom($plugin->mpm_plugin_id) !== null)
            ->values()
            ->map(fn (Plugins $plugin): array => [
                'id' => $plugin->mpm_plugin_id,
                'name' => $this->mpmPluginService->getById($plugin->mpm_plugin_id)->name,
            ]);
    }
}
