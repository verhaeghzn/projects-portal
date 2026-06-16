<?php

use App\Helpers\SamlHelper;
use App\Http\Controllers\Auth\SamlController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// SAML routes (ACS and SLS are excluded from CSRF in bootstrap/app.php)
Route::get('/saml/login', [SamlController::class, 'login'])->name('saml.login');
Route::get('/saml/link', [SamlController::class, 'link'])->middleware(['web', 'auth'])->name('saml.link');
Route::post('/saml/acs', [SamlController::class, 'acs'])->name('saml.acs');
Route::get('/saml/logout', [SamlController::class, 'logout'])->name('saml.logout');
Route::get('/saml/sls', [SamlController::class, 'sls'])->name('saml.sls');
Route::get('/saml/metadata', [SamlController::class, 'metadata'])->name('saml.metadata');

// Public routes - require SAML auth only if SAML login is required
$middleware = SamlHelper::isLoginRequired() ? [\App\Http\Middleware\RedirectToSamlLogin::class] : [];
Route::middleware($middleware)->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/past', [ProjectController::class, 'past'])->name('projects.past');

    // Division project listings. Canonical URLs use the division abbreviation (e.g. /projects/cem).
    // Old slug-based URLs (e.g. /projects/computational-experimental-mechanics) are redirected for backwards compatibility.
    $divisionAbbrevs = [
        'thermo-fluids-engineering' => 'tfe',
        'computational-experimental-mechanics' => 'cem',
        'dynamical-systems-design' => 'dsd',
    ];

    foreach ($divisionAbbrevs as $divisionSlug => $abbrev) {
        Route::get('/projects/'.$abbrev, [ProjectController::class, 'index'])
            ->defaults('division', $divisionSlug)
            ->name('projects.division.'.$divisionSlug);

        Route::get('/projects/'.$divisionSlug, function (Request $request) use ($divisionSlug) {
            return redirect()->route('projects.division.'.$divisionSlug, $request->query(), 301);
        });
    }

    Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy');
});

Route::get('/onboarding/{token}', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding/{token}', [OnboardingController::class, 'store'])->name('onboarding.store');
