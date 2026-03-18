<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use Illuminate\Support\Collection;

class PaymentInitializationService {
    public function __construct(
        private CashTransactionService $cashTransactionService,
        private PaystackTransactionService $paystackTransactionService,
        private PluginsService $pluginsService,
        private MpmPluginService $mpmPluginService,
    ) {}

    /**
     * Initialize a payment with any enabled provider.
     *
     * @param int         $providerId MpmPlugin constant (0 = cash)
     * @param string      $sender     Phone number of the payer
     * @param string      $message    Transaction routing key (device serial or entity ID)
     * @param string      $type       Transaction type (e.g. 'deferred_payment', 'energy')
     * @param int         $customerId Person ID of the customer
     * @param int         $creatorId  Admin user ID (used for cash transactions)
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
        int $creatorId,
        ?string $serialId = null,
    ): array {
        $validProviderIds = [
            0, // cash
            MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
        ];

        if (!in_array($providerId, $validProviderIds, true)) {
            throw new \InvalidArgumentException("Unsupported payment provider ID: {$providerId}");
        }

        return match ($providerId) {
            MpmPlugin::PAYSTACK_PAYMENT_PROVIDER => $this->paystackTransactionService->initializePayment(
                $amount,
                $sender,
                $message,
                $type,
                $customerId,
                $serialId,
            ),
            default => $this->initializeCash($creatorId, $amount, $sender, $message, $type),
        };
    }

    /**
     * @return array{transaction: Transaction, provider_data: array<string, mixed>}
     */
    private function initializeCash(
        int $creatorId,
        float $amount,
        string $sender,
        string $message,
        string $type,
    ): array {
        $transaction = $this->cashTransactionService->createTransaction(
            $creatorId,
            $amount,
            $sender,
            $message,
            $type,
        );

        return ['transaction' => $transaction, 'provider_data' => []];
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
