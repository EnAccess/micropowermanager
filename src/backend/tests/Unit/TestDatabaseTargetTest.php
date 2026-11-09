<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestDatabaseTarget;

class TestDatabaseTargetTest extends TestCase {
    public function testItAllowsOnlyDocumentedDedicatedTestEndpoints(): void {
        $this->assertTrue(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', '127.0.0.1', '53306', ''));
        $this->assertTrue(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', 'mysql_testing', '3306', ''));
    }

    public function testItRejectsDevelopmentDatabasesAndUnexpectedEndpoints(): void {
        $this->assertFalse(TestDatabaseTarget::isApproved('mysql', 'micro_power_manager', '127.0.0.1', '53306', ''));
        $this->assertFalse(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', '127.0.0.1', '3306', ''));
        $this->assertFalse(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', 'localhost', '53306', ''));
        $this->assertFalse(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', 'mysql', '3306', ''));
        $this->assertFalse(TestDatabaseTarget::isApproved('sqlite', 'mpm_testing', '127.0.0.1', '53306', ''));
        $this->assertFalse(TestDatabaseTarget::isApproved('mysql', 'mpm_testing', '127.0.0.1', '53306', '/tmp/mysql.sock'));
    }
}
