<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ImportServices;

use App\Services\ImportServices\ImportResult;
use Tests\TestCase;

class ImportResultTest extends TestCase {
    public function testToArrayDerivesSuccessAndCountsFromTheItemLists(): void {
        $result = new ImportResult(
            message: 'Devices imported successfully',
            added: [['id' => 1]],
            modified: [['id' => 2]],
            failed: [['serial_number' => 'X-1', 'errors' => ['import' => 'boom']]],
        );

        $this->assertSame([
            'success' => true,
            'message' => 'Devices imported successfully',
            'imported_count' => 2,
            'added_count' => 1,
            'modified_count' => 1,
            'failed_count' => 1,
            'added' => [['id' => 1]],
            'modified' => [['id' => 2]],
            'failed' => [['serial_number' => 'X-1', 'errors' => ['import' => 'boom']]],
        ], $result->toArray());
    }

    public function testSuccessIsFalseOnlyWhenNothingImportedAndSomethingFailed(): void {
        $allFailed = new ImportResult(
            message: 'All device imports failed',
            added: [],
            modified: [],
            failed: [['serial_number' => 'X-1', 'errors' => ['import' => 'boom']]],
        );
        $emptyImport = new ImportResult(
            message: 'Devices imported successfully',
            added: [],
            modified: [],
            failed: [],
        );

        $this->assertFalse($allFailed->success());
        $this->assertTrue($emptyImport->success());
    }
}
