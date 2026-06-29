<?php

namespace App\Plugins\BulkRegistration\Exceptions;

/**
 * Thrown during bulk registration when the cluster name is missing or no
 * matching cluster can be found.
 */
class ClusterNotFoundException extends MissingDataException {}
