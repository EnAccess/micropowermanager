<?php

namespace App\Lib;

/**
 * Implemented by the service provider of a plugin that vends tokens. Laravel
 * already keeps every registered provider, so core finds the vending plugins by
 * asking for the providers of this type, and each plugin states in code which
 * mpm_plugins row it is and which class issues its tokens.
 */
interface IVendingPluginProvider {
    public function mpmPluginId(): int;

    /**
     * @return class-string<ITokenIssuer>
     */
    public function tokenIssuer(): string;
}
