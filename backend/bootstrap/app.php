<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\ReadAuthCookie::class,
        ]);
        $middleware->alias([
            'role.admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role.manager' => \App\Http\Middleware\ManagerMiddleware::class,
            'role.regular' => \App\Http\Middleware\RegularUserMiddleware::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'token.version' => \App\Http\Middleware\ValidateTokenVersion::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
