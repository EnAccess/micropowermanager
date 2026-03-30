<?php

namespace Tests\Unit;

// use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\TestCase;

class TestingEnvironmentTest extends TestCase {
    /**
     * Make sure our tests are run in the expected environment.
     */
    public function testCorrectTestingEnvironmentIsUsed(): void {
        $this->assertEquals('testing', app()->environment());
        $this->assertEquals('.env.testing', app()->environmentFile());
    }
}
