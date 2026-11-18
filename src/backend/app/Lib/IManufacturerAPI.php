<?php

namespace App\Lib;

/**
 * A manufacturer's API, which vends tokens for the devices it made. Core tells it
 * apart from a vending-only {@see ITokenIssuer}: a manufacturer's plugin is only
 * offered for its own devices, while a vending-only plugin is offered for any.
 */
interface IManufacturerAPI extends ITokenIssuer {}
