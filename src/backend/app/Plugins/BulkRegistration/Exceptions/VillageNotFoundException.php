<?php

namespace App\Plugins\BulkRegistration\Exceptions;

/**
 * Thrown during bulk registration when the village name is missing or no
 * matching village can be found.
 */
class VillageNotFoundException extends MissingDataException {}
