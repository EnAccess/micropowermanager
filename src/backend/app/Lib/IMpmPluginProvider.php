<?php

namespace App\Lib;

/**
 * Implemented by the main service provider of every plugin. Laravel keeps every
 * registered provider, so the plugin catalog is the set of providers of this type
 * and each plugin describes itself here instead of in a central list.
 */
interface IMpmPluginProvider {
    /**
     * Unique snake_case identifier tenants, devices and API clients refer to the plugin by.
     */
    public function slug(): string;

    public function displayName(): string;

    public function description(): string;

    /**
     * One of `mini-grid`, `shs`, `e-bike` or `general`.
     */
    public function usageType(): string;
}
