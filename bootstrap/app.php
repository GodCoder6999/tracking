<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function ($request) {
            if (str_starts_with($request->path(), 'owner')) return route('login.owner');
            if (str_starts_with($request->path(), 'client')) return route('login.client');
            return route('login.dealer');
        });

        $middleware->redirectUsersTo(function ($request) {
            $path = $request->path();
            if (str_contains($path, 'client')) return route('client.dashboard');
            if (str_contains($path, 'dealer')) return route('dealer.dashboard');
            return route('owner.dashboard');
        });

        $middleware->alias([
            'role'            => \App\Http\Middleware\EnsureRole::class,
            'api.token'       => \App\Http\Middleware\ApiToken::class,
            'device.approved' => \App\Http\Middleware\DeviceApproved::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
