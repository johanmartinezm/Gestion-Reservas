<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base de las excepciones de reglas de negocio. Se renderizan como JSON con un
 * código de error legible y un status HTTP 422 (Unprocessable Entity).
 */
abstract class DomainException extends RuntimeException
{
    /**
     * Código de error estable para el cliente (snake_case).
     */
    abstract public function errorCode(): string;

    public function statusCode(): int
    {
        return 422;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'error' => $this->errorCode(),
            'message' => $this->getMessage(),
        ], $this->statusCode());
    }
}
