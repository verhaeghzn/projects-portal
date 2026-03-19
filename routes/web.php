<?php

use App\Helpers\SamlHelper;
use App\Http\Controllers\Auth\SamlController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\ProjectController;
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
    Route::get('/projects/thermo-fluids-engineering', [ProjectController::class, 'index'])->defaults('division', 'thermo-fluids-engineering')->name('projects.division.thermo-fluids-engineering');
    Route::get('/projects/computational-experimental-mechanics', [ProjectController::class, 'index'])->defaults('division', 'computational-experimental-mechanics')->name('projects.division.computational-experimental-mechanics');
    Route::get('/projects/dynamical-systems-design', [ProjectController::class, 'index'])->defaults('division', 'dynamical-systems-design')->name('projects.division.dynamical-systems-design');
    Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/contact', [ContactController::class, 'index'])->name('contact');
    Route::get('/privacy', [PrivacyController::class, 'index'])->name('privacy');
});

Route::get('/onboarding/{token}', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::post('/onboarding/{token}', [OnboardingController::class, 'store'])->name('onboarding.store');

// Admin impersonation – must be authenticated
Route::middleware(['web', 'auth'])->prefix('admin')->group(function () {
    Route::get('impersonate/{user}', [ImpersonateController::class, 'start'])->name('admin.impersonate.start');
    Route::get('impersonate/leave', [ImpersonateController::class, 'leave'])->name('admin.impersonate.leave');
});
