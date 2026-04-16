<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Services\Interfaces\PaymentInitializer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class PaymentInitializationService {
    /**
     * Maps provider IDs to their PaymentInitializer implementation class.
     * When adding a new payment provider plugin, register it here.
     *
     * @var array<int, class-string<PaymentInitializer>>
     */
    private const PROVIDER_MAP = [
        0 => CashTransactionService::class,
        MpmPlugin::PAYSTACK_PAYMENT_PROVIDER => PaystackTransactionService::class,
    ];

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
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
     */
    public function initialize(
        int $providerId,
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        if (!isset(self::PROVIDER_MAP[$providerId])) {
            throw new \InvalidArgumentException("Unsupported payment provider ID: {$providerId}");
        }

        $initializer = $this->container->make(self::PROVIDER_MAP[$providerId]);

        return $initializer->initializePayment(
            $amount,
            $sender,
            $message,
            $type,
            $customerId,
            $serialId,
        );
    }

    /** @return Collection<int, array{id: int, name: string}> */
    public function paymentProviders(): Collection {
        $activePlugins = $this->pluginsService->getActivePaymentProviders();

        return $activePlugins->map(function (Plugins $plugin): array {
            $mpmPlugin = $this->mpmPluginService->getById($plugin->mpm_plugin_id);

            return ['id' => $plugin->mpm_plugin_id, 'name' => $mpmPlugin instanceof MpmPlugin ? $mpmPlugin->name : 'Unknown'];
        });
    }
}
