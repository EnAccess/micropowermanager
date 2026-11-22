<?php

namespace App\Lib;

/**
 * Implemented by the service provider of a manufacturer's plugin: a vending plugin
 * that also registers a manufacturer, so it is the default vending plugin for that
 * manufacturer's devices and its token issuer is that manufacturer's API. The
 * manufacturer's api_name is the short class name of that token issuer.
 */
interface IManufacturerPluginProvider extends IVendingPluginProvider {}
