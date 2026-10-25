<?php

use App\Exceptions\BusinessRuleException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'admin.role'       => \App\Http\Middleware\AdminRoleMiddleware::class,
            'admin.permission' => \App\Http\Middleware\AdminPermissionMiddleware::class,

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Always return JSON for API routes (no redirects or HTML pages)
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Business rule errors (422 by default, or the status you set)
        $exceptions->render(function (\DomainException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request failed',
                    'data'    => null,
                    'error'   => $e->getMessage(),
                ], $e instanceof BusinessRuleException ? $e->status : 422);
            }
        });

        // Not found
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'data'    => null,
                    'error'   => 'Not found',
                ], 404);
            }
        });

        // Missing or invalid token
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'data'    => null,
                    'error'   => 'Unauthenticated',
                ], 401);
            }
        });

        // Form Request / validate() failures
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'data'    => null,
                    'error'   => $e->errors(),
                ], 422);
            }
        });

        // Other HTTP errors such as 403 and 429
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP error',
                    'data'    => null,
                    'error'   => $e->getMessage() ?: 'HTTP error',
                ], $e->getStatusCode());
            }
        });
    })->create();