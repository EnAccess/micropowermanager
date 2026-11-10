<?php

namespace App\Plugins\WhatsappBusinessApi\Exceptions;

use App\Exceptions\MpmException;

class MessageNotSentException extends MpmException {
    protected int $httpStatusCode = 502;
}
