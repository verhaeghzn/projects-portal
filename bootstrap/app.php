<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'saml/acs',
            'saml/sls',
        ]);
        // Redirect old production domain to APP_URL (path + query preserved)
        $middleware->prepend(\App\Http\Middleware\RedirectOldDomain::class);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('avatars:resize')->dailyAt('01:00');
        $schedule->command('projects:randomize-rankings')->dailyAt('02:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        \Spatie\LaravelFlare\Facades\Flare::handles($exceptions);
    })->create();
