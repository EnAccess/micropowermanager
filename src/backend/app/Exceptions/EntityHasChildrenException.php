<?php

namespace App\Exceptions;

/**
 * Thrown when an entity cannot be soft-deleted because it still has non-trashed
 * children. Soft-deletes should not leave orphaned child records in a state where
 * they point to a tombstone — the caller is expected to delete children first.
 */
class EntityHasChildrenException extends \Exception {}
