<?php

namespace App\Plugins\BulkRegistration\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a CSV file submitted for bulk registration cannot be parsed.
 */
class CsvDataParserException extends MpmException {}
