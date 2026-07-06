<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ImportFailedException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImportFailedExceptionTest extends TestCase {
    public function testErrorsReturnsTheGivenArray(): void {
        $errors = ['device_0.device_info.serial_number' => 'Serial number is required'];

        $exception = new ImportFailedException($errors);

        $this->assertSame($errors, $exception->errors());
    }

    public function testRenderReproducesTheLegacySuccessErrorsShapeAt422(): void {
        $errors = ['transaction' => 'Failed to import devices: something went wrong'];

        $exception = new ImportFailedException($errors);
        $response = $exception->render(new Request());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            ['success' => false, 'errors' => $errors],
            json_decode($response->getContent(), true)
        );
    }
}
