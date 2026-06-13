<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Base de las excepciones de reglas de negocio. Se renderizan como un documento
 * "problem+json" (RFC 7807) con un código de error estable y su status HTTP.
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
            'type' => "/problems/{$this->errorCode()}",
            'title' => Str::headline($this->errorCode()),
            'status' => $this->statusCode(),
            'detail' => $this->getMessage(),
            'code' => $this->errorCode(),
        ], $this->statusCode(), ['Content-Type' => 'application/problem+json']);
    }
}
