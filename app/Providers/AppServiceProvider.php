<?php

namespace App\Providers;

use App\Auth\Guards\StudentsGuard;
use App\Auth\Providers\StudentsProvider;
use App\Models\Division;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom students guard
        Auth::extend('students', function (Application $app, string $name, array $config) {
            $session = $app['session.store'];
            $provider = new StudentsProvider($session);
            return new StudentsGuard($provider, $session);
        });

        // Register custom students provider
        Auth::provider('students', function (Application $app, array $config) {
            $session = $app['session.store'];
            return new StudentsProvider($session);
        });

        LogViewer::auth(function ($request) {
            return Auth::check() && Auth::user()->hasRole('Administrator');
        });

        View::share('divisions', Schema::hasTable('divisions') ? Division::orderBy('name')->get() : collect());
    }
}
