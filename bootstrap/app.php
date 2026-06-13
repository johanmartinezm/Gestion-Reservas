<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Errores de validación de forma como problem+json (RFC 7807).
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'type' => '/problems/validation',
                'title' => 'Validation Failed',
                'status' => 422,
                'detail' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422, ['Content-Type' => 'application/problem+json']);
        });

        // Recurso no encontrado (binding o findOrFail) como problem+json.
        $notFound = function (Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'type' => '/problems/not-found',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Recurso no encontrado.',
            ], 404, ['Content-Type' => 'application/problem+json']);
        };

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $notFound($request));
        $exceptions->render(fn (ModelNotFoundException $e, Request $request) => $notFound($request));
    })->create();
