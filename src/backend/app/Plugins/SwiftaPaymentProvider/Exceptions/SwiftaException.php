<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base class for every error the Swifta endpoints can report.
 *
 * Swifta reads `success` to decide whether a call worked, so its errors carry a
 * body shape of their own rather than the `{"message": ...}` the rest of MPM
 * answers with. Extending this is what puts a thrown error on that contract
 * wherever it surfaces, so no middleware has to catch it to build the body.
 *
 * Subclasses that are the caller's fault keep the default 400; one caused by
 * our own state should say so with a 5xx so Swifta retries it.
 */
abstract class SwiftaException extends MpmException {
    protected int $httpStatusCode = 400;

    public function render(Request $request): JsonResponse {
        return response()->json([
            'success' => 0,
            'message' => $this->getMessage(),
        ], $this->httpStatusCode);
    }
}
