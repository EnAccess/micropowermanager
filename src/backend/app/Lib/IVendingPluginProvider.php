<?php

namespace App\Lib;

/**
 * Implemented by the service provider of a plugin that vends tokens. Core finds the
 * vending plugins by asking Laravel for the registered providers of this type, and
 * each one states in code which class issues its tokens.
 */
interface IVendingPluginProvider extends IMpmPluginProvider {
    /**
     * @return class-string<ITokenIssuer>
     */
    public function tokenIssuer(): string;
}
