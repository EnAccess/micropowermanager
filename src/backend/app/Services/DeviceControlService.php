<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\TransactionDataContainer;
use App\Enums\DeviceTokenUnit;
use App\Enums\DeviceType;
use App\Enums\ManufacturerMappingStatus;
use App\Enums\VendingCapability;
use App\Events\NewLogEvent;
use App\Exceptions\Device\CreditPriceNotFoundException;
use App\Exceptions\Device\DeviceIsNotAssignedToCustomer;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Exceptions\MpmException;
use App\Exceptions\VendingPluginUnavailableException;
use App\Lib\DeviceCapabilities;
use App\Lib\DeviceMappingResult;
use App\Lib\IManufacturerDeviceControl;
use App\Lib\IManufacturerPluginProvider;
use App\Lib\ITokenIssuer;
use App\Lib\IVendingPluginProvider;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Everything MPM asks of a single device's APIs outside the payment flow: read its
 * status from its manufacturer, issue a token on demand through the plugin that
 * vends it. It is the one place that turns a device into an API, so callers never
 * touch plugin providers themselves.
 */
class DeviceControlService {
    public function __construct(
        private CashTransactionService $cashTransactionService,
        private AppliancePaymentService $appliancePaymentService,
        private PluginsService $pluginsService,
        private ManufacturerService $manufacturerService,
    ) {}

    public function capabilities(Device $device): DeviceCapabilities {
        $blocker = null;
        $declared = [];

        try {
            $declared = $this->resolveVendingApi($device)->capabilities();
            $this->assertTokenGenerationPossible($device);
        } catch (MpmException $e) {
            $blocker = $e;
        }

        return new DeviceCapabilities(
            tokenGeneration: !$blocker instanceof MpmException,
            unlockToken: in_array(VendingCapability::UnlockToken, $declared, true),
            resetToken: in_array(VendingCapability::ResetToken, $declared, true),
            creditUnit: DeviceType::from($device->device_type)->creditUnit(),
            tokenGenerationBlockedReason: $blocker?->getMessage(),
        );
    }

    /**
     * Issues a token straight from the manufacturer, without a customer payment.
     * The credit is recorded as an ad-hoc transaction because every manufacturer API
     * writes its own reference onto one, and because credit an operator issued by hand
     * should stay visible next to the payments that supplied the rest of it.
     *
     * Access rates, appliance installments and the minimum purchase amount are
     * deliberately not applied — this issues credit, it does not settle a debt.
     */
    public function generateToken(Device $device, float $amount, DeviceTokenUnit $unit, int $creatorId): Token {
        $api = $this->assertTokenGenerationPossible($device);
        $amountInCurrency = $this->toCurrency($device, $amount, $unit);

        return $this->issueToken(
            $device,
            $amountInCurrency,
            $creatorId,
            fn (TransactionDataContainer $container) => $api->chargeDevice($container),
            fn (Token $token) => "Ad-hoc token generated for device {$device->device_serial}: {$token}",
        );
    }

    /**
     * Hands the unit to its customer for good, so it keeps working without further
     * payment. {@see self::generateResetToken()} for the terms both share.
     */
    public function generateUnlockToken(Device $device, int $creatorId): Token {
        $api = $this->assertCapability($device, VendingCapability::UnlockToken);

        return $this->issueToken(
            $device,
            0.0,
            $creatorId,
            fn (TransactionDataContainer $container) => $api->unlockDevice($container),
            fn (Token $token) => "Unlock token generated for device {$device->device_serial}: {$token->token}",
        );
    }

    /**
     * Takes the unit's remaining credit down to zero, so a repossessed unit stops
     * running on days already paid for and can be resold under a new plan.
     *
     * Unlike issuing credit, this asks nothing of the device's state — a unit being
     * repossessed has usually already lost its payment plan, and may have lost its
     * customer too. Only the manufacturer has to support the action. Nothing is
     * changed on the MPM side: releasing the unit stays a separate, deliberate step.
     */
    public function generateResetToken(Device $device, int $creatorId): Token {
        $api = $this->assertCapability($device, VendingCapability::ResetToken);

        return $this->issueToken(
            $device,
            0.0,
            $creatorId,
            // clearDevice reads the unit off the device rather than the transaction, so
            // the container it is handed goes unused here.
            fn (TransactionDataContainer $container) => $api->clearDevice($device)
                ?? throw new ApiCallDoesNotSupportedException("The manufacturer returned no reset token for device {$device->device_serial}."),
            fn (Token $token) => "Reset token generated for device {$device->device_serial}: {$token->token}",
        );
    }

    /**
     * Writes the ad-hoc transaction, asks the manufacturer for the token and saves
     * it against that transaction.
     *
     * The manufacturer call needs a persisted transaction to write its reference onto,
     * so the transaction row is created first and rolled back when the call fails — a
     * transaction row left behind without a token reads as revenue nobody was charged.
     * Every row lives on the tenant connection, which the request does not make the
     * default one, so the database transaction has to name it.
     *
     * @param \Closure(TransactionDataContainer): array<string, mixed> $vendToken
     * @param \Closure(Token): string                                  $logAction
     */
    private function issueToken(
        Device $device,
        float $amountInCurrency,
        int $creatorId,
        \Closure $vendToken,
        \Closure $logAction,
    ): Token {
        $senderPhone = (string) ($device->person?->addresses()->value('phone') ?? '');

        $token = DB::connection('tenant')->transaction(function () use ($device, $amountInCurrency, $senderPhone, $creatorId, $vendToken) {
            $transaction = $this->cashTransactionService->createTransaction(
                $creatorId,
                $amountInCurrency,
                $senderPhone,
                $device->device_serial,
                Transaction::TYPE_AD_HOC,
            );

            $tokenData = $vendToken(TransactionDataContainer::initialize($transaction));

            $token = Token::query()->make($tokenData);
            $token->device_id = $device->id;
            $token->transaction()->associate($transaction);
            $token->save();

            return $token;
        });

        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $device,
            'action' => $logAction($token),
        ]));

        return $token;
    }

    /**
     * Everything that has to hold before a device can be issued credit out of band:
     * an API that vends tokens, a customer to record the transaction against — several
     * manufacturers vend against the customer record, not the unit — and a price to
     * turn money into credit. {@see self::capabilities()} reports a failure as a
     * reason and {@see self::generateToken()} raises it, so the endpoint and the UI
     * cannot disagree on the rules.
     */
    private function assertTokenGenerationPossible(Device $device): ITokenIssuer {
        $api = $this->resolveVendingApi($device);

        if (!in_array(VendingCapability::CreditToken, $api->capabilities(), true)) {
            throw new ApiCallDoesNotSupportedException('The vending provider of this device does not vend credit tokens.');
        }

        if ($device->person === null) {
            throw new DeviceIsNotAssignedToCustomer("Device {$device->device_serial} is not assigned to a customer, so credit cannot be issued for it.");
        }

        $this->creditPrice($device);

        return $api;
    }

    /**
     * Every vending API serves a different subset of the calls, and a reset cannot be
     * attempted just to find out whether it works, so the API's own declaration
     * decides. {@see self::capabilities()} reports the same set, so the endpoint and
     * the UI cannot disagree on what is on offer.
     */
    private function assertCapability(Device $device, VendingCapability $capability): ITokenIssuer {
        $api = $this->resolveVendingApi($device);

        if (!in_array($capability, $api->capabilities(), true)) {
            throw new ApiCallDoesNotSupportedException("The vending provider of this device does not support the {$capability->value} capability.");
        }

        return $api;
    }

    /**
     * Asks the device's manufacturer API whether the unit is mapped on the
     * manufacturer side, so users can diagnose unit remappings before a payment
     * fails. Manufacturers without a device management API report as unsupported.
     */
    public function verifyManufacturerMapping(Device $device): DeviceMappingResult {
        $api = $this->manufacturerService->api($device->device->manufacturer);

        if (!$api instanceof IManufacturerDeviceControl) {
            return new DeviceMappingResult(supported: false);
        }

        $deviceInfo = $api->getDeviceInfo($device);

        return new DeviceMappingResult(supported: true, mapped: $deviceInfo['mapped'], device: $deviceInfo['device']);
    }

    /**
     * Runs the mapping check and persists its outcome on the device, so the
     * status is available in lists and exports without re-querying the
     * manufacturer.
     */
    public function refreshManufacturerMapping(Device $device): DeviceMappingResult {
        $result = $this->verifyManufacturerMapping($device);

        $device->update([
            'manufacturer_mapping_status' => $this->mappingStatus($result),
            'manufacturer_mapping_checked_at' => now(),
        ]);

        return $result;
    }

    /**
     * A plugin vends only while the tenant has it active, a manufacturer's own plugin
     * included: a payment through an inactive plugin fails with the reason, so the
     * tenant can see why and act on it.
     */
    public function resolveVendingApi(Device $device): ITokenIssuer {
        $vendingPlugin = $device->vendingPlugin;

        if (!$vendingPlugin instanceof MpmPlugin) {
            throw new VendingPluginUnavailableException("Device {$device->device_serial} has no vending provider.");
        }

        if (!$this->pluginsService->isPluginActive($vendingPlugin->id)) {
            throw new VendingPluginUnavailableException("The vending provider {$vendingPlugin->name} of device {$device->device_serial} is not active.");
        }

        $provider = $this->vendingPluginProviders()[$vendingPlugin->id]
            ?? throw new VendingPluginUnavailableException("The vending provider {$vendingPlugin->name} of device {$device->device_serial} is not installed.");

        return resolve($provider->tokenIssuer());
    }

    public function manufacturerVendingPluginId(Device $device): ?int {
        $manufacturer = $device->device()->getResults()?->manufacturer;

        if ($manufacturer === null) {
            return null;
        }

        return $this->manufacturerService->pluginProvider($manufacturer)?->mpmPluginId();
    }

    /**
     * The plugins a device may be moved to: its manufacturer's plugin, and every
     * active vending-only plugin. Another manufacturer's plugin is left out, since it
     * can only vend the devices it made.
     *
     * @return Collection<int, MpmPlugin>
     */
    public function vendingPluginOptions(Device $device): Collection {
        $vendingOnlyPluginIds = array_keys(array_filter(
            $this->vendingPluginProviders(),
            fn (IVendingPluginProvider $provider): bool => !$provider instanceof IManufacturerPluginProvider,
        ));

        return MpmPlugin::query()
            ->where('id', $this->manufacturerVendingPluginId($device))
            ->orWhere(fn (Builder $vendingOnly) => $vendingOnly
                ->whereIn('id', $vendingOnlyPluginIds)
                ->whereIn('id', $this->pluginsService->getActiveMpmPluginIds()))
            ->orderBy('name')
            ->get();
    }

    /**
     * Null moves the device back to its manufacturer's plugin.
     */
    public function updateVendingPlugin(Device $device, ?int $vendingPluginId): Device {
        $vendingPluginId ??= $this->manufacturerVendingPluginId($device);

        if ($vendingPluginId !== null && $this->vendingPluginOptions($device)->doesntContain('id', $vendingPluginId)) {
            throw ValidationException::withMessages(['vending_plugin_id' => ['This vending provider cannot vend this device.']]);
        }

        $device->update(['vending_plugin_id' => $vendingPluginId]);

        return $device->load('vendingPlugin');
    }

    /**
     * @return array<int, IVendingPluginProvider>
     */
    private function vendingPluginProviders(): array {
        /** @var array<string, IVendingPluginProvider> $providers */
        $providers = app()->getProviders(IVendingPluginProvider::class);

        return collect($providers)
            ->keyBy(fn (IVendingPluginProvider $provider): int => $provider->mpmPluginId())
            ->all();
    }

    /**
     * Manufacturer APIs only vend against a currency amount, so a request made in
     * credit is priced at the same rate the API will divide by on the way back.
     * The credit finally issued is whatever the manufacturer returns — several
     * round their result — so callers must report the token, not the request.
     */
    private function toCurrency(Device $device, float $amount, DeviceTokenUnit $unit): float {
        if ($unit === DeviceTokenUnit::Currency) {
            return $amount;
        }

        $deviceType = DeviceType::from($device->device_type);

        if ($unit !== $deviceType->creditUnit()) {
            throw new CreditPriceNotFoundException("A {$deviceType->value} issues its credit in {$deviceType->creditUnit()->value}, not in {$unit->value}.");
        }

        return $amount * $this->creditPrice($device);
    }

    /**
     * What one credit unit costs. Manufacturer APIs divide the currency amount they
     * are handed by this same price to decide how much credit to vend, so a device
     * that cannot be priced cannot be charged at all — not even for a request already
     * denominated in currency.
     */
    private function creditPrice(Device $device): float {
        $deviceModel = $device->device;

        if ($deviceModel instanceof Meter) {
            $tariff = $deviceModel->tariff()->first();

            if ($tariff === null || $tariff->total_price <= 0) {
                throw new CreditPriceNotFoundException('This meter has no tariff, so an energy amount cannot be priced.');
            }

            return (float) $tariff->total_price;
        }

        /** @var AppliancePerson|null $appliancePerson */
        $appliancePerson = $device->appliancePerson()->latest()->first();

        $dailyPrice = $appliancePerson === null
            ? 0.0
            : $this->appliancePaymentService->getDailyPrice($appliancePerson);

        if ($dailyPrice <= 0) {
            throw new CreditPriceNotFoundException('This device has no appliance plan with a daily price, so a number of days cannot be priced.');
        }

        return $dailyPrice;
    }

    private function mappingStatus(DeviceMappingResult $result): ManufacturerMappingStatus {
        if (!$result->supported) {
            return ManufacturerMappingStatus::Unsupported;
        }

        return $result->mapped
            ? ManufacturerMappingStatus::Mapped
            : ManufacturerMappingStatus::NotMapped;
    }
}
