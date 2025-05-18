<?php

namespace App\Traits;

use App\Exceptions\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

trait RestExceptionHandler {
    /**
     * Creates a new response based on exception type.
     *
     * @param Request    $request
     * @param \Exception $e
     *
     * @return JsonResponse
     */
    protected function getJsonResponseForException(Request $request, \Exception|\Throwable $e) {
        switch (true) {
            case $e instanceof TokenExpiredException:
                return response()->json(['error' => 'Token is expired'], 401);
            case $e instanceof TokenInvalidException:
                return response()->json(['error' => 'Token is invalid'], 401);
            case $e instanceof JWTException:
                return response()->json(['error' => 'Unauthorized. '.$e->getMessage().' Make sure you are logged in.'], 401);
            case $e instanceof ModelNotFoundException:
                $response = $this->modelNotFound('model not found '.implode(' ', $e->getIds()));
                break;
            case $e instanceof ValidationException:
                $response = $this->validationError($e);
                break;
            default:
                $response = $this->badRequest($e->getMessage());
        }

        return $response;
    }

    /**
     * returns a json response for all excepion types except modelnotfoundexception.
     *
     * @param string $message
     * @param int    $status_code
     *
     * @return JsonResponse
     */
    protected function badRequest($message = 'Bad request', $status_code = 400) {
        return $this->jsonResponse(
            [
                'message' => $message,
                'status_code' => $status_code,
            ],
            $status_code
        );
    }

    /**
     * Returns a json response for Model not found exception.
     *
     * @param string $message
     * @param int    $status_code
     *
     * @return JsonResponse
     */
    protected function modelNotFound($message = 'Record not found', $status_code = 404) {
        return $this->jsonResponse(
            [
                'message' => $message,
                'status_code' => $status_code,
            ],
            $status_code
        );
    }

    /**
     * Generates validation error response.
     *
     * @param \Illuminate\Support\MessageBag|array|string $errors
     * @param int          $status_code
     *
     * @return JsonResponse
     */
    protected function validationError($errors = 'Validation failed', $status_code = 422) {
        $errorMessages = ($errors instanceof \Illuminate\Support\MessageBag) ? $errors->toArray() : [$errors];

        return $this->jsonResponse(
            [
                'message' => 'Validation failed',
                'errors' => $errorMessages,
                'status_code' => $status_code,
            ],
            $status_code
        );
    }

    /**
     * Determines if the exception type is Model not found exception.
     *
     * @param \Exception $e
     *
     * @return bool
     */
    protected function isModelNotFoundException($e): bool {
        return $e instanceof ModelNotFoundException;
    }

    /**
     * Determines if given Exception is Validation Exception.
     *
     * @param \Exception $e
     *
     * @return bool
     */
    protected function isValidationException($e): bool {
        return $e instanceof ValidationException;
    }

    /**
     * Generates a json response & returns it.
     *
     * @param array|null $payload
     * @param            $status_code
     *
     * @return JsonResponse
     */
    protected function jsonResponse(?array $payload = null, $status_code = 400) {
        $payload = $payload ?: [];

        return response()->json(
            ['data' => $payload],
            $status_code
        );
    }
}
