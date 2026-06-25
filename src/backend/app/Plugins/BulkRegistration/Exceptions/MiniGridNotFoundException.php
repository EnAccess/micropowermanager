<?php

namespace App\Plugins\BulkRegistration\Exceptions;

/**
 * Thrown during bulk registration when the mini grid name is missing or no
 * matching mini grid can be found.
 */
class MiniGridNotFoundException extends MissingDataException {}
