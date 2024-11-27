<?php

namespace Inensus\BulkRegistration\Services;

interface ICreatorService {
    public function createRelatedDataIfDoesNotExists($resolvedCsvData);
}
