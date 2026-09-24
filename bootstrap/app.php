<?php

use App\Http\Middleware\EnsureClinicModule;
use App\Http\Middleware\EnsureClinicStaff;
use App\Http\Middleware\EnsureHermesAgent;
use App\Http\Middleware\EnsureInternalStaff;
use App\Http\Middleware\RecordPageView;
use App\Http\Middleware\SetLocale;
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
        $middleware->web(append: [
            SetLocale::class,
            RecordPageView::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login');
        });

        $middleware->alias([
            'internal-staff' => EnsureInternalStaff::class,
            'clinic-staff' => EnsureClinicStaff::class,
            'clinic-module' => EnsureClinicModule::class,
            'hermes' => EnsureHermesAgent::class,
        ]);

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
