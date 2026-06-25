<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Base class for all MicroPowerManager domain / business-logic errors.
 *
 * Extend this for any error that represents a violated business rule or an
 * unprocessable domain state — regardless of where it is thrown (service,
 * listener, scheduled command, controller, ...).
 *
 * When such an error happens to surface from an HTTP request, Laravel calls
 * {@see self::render()} and it is turned into a JSON `{"message": ...}` body
 * with the {@see self::$httpStatusCode} status. Outside of a request (e.g. in a
 * queue worker or scheduler) the HTTP concern is simply ignored.
 *
 * Subclasses express their meaning by overriding {@see self::$httpStatusCode}
 * (defaults to 422 Unprocessable Entity) and by carrying a human-readable
 * message.
 */
abstract class MpmException extends \Exception {
    protected int $httpStatusCode = 422;

    public function render(Request $request): JsonResponse {
        return response()->json(['message' => $this->getMessage()], $this->httpStatusCode);
    }
}
