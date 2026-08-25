<?php

use App\Http\Middleware\AssignRequestId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('events') || $request->expectsJson()
        );

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('events')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                    'details' => (object) [],
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('events')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'METHOD_NOT_ALLOWED',
                    'message' => 'The HTTP method is not allowed for this endpoint.',
                    'details' => (object) [],
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 405);
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('events')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An unexpected error occurred.',
                    'details' => (object) [],
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 500);
        });
    })->create();
