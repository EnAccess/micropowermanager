<?php

namespace App\Plugins\WhatsappMessaging\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a message must be sent as a template (the customer service window is closed) but no usable template mapping exists.
 * The caller falls back to SMS instead of sending a message Meta would reject.
 */
class WhatsappTemplateNotConfiguredException extends MpmException {}
