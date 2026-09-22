<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    seedTestData();
});

test('web login activates a pending invited user', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'pending-token',
        'invitation_sent_at' => now()->subDay(),
    ]);
    $user->assignRole('Researcher');

    Auth::guard('web')->login($user);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->invitation_token)->toBeNull()
        ->and($user->invitation_sent_at)->toBeNull();
});

test('students guard login does not activate a pending invited user', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'pending-token',
        'invitation_sent_at' => now(),
    ]);

    event(new Illuminate\Auth\Events\Login('students', $user, false));

    $user->refresh();
    expect($user->email_verified_at)->toBeNull()
        ->and($user->invitation_token)->toBe('pending-token');
});

test('already activated users are left unchanged on login', function () {
    $verifiedAt = now()->subWeek();
    $user = User::factory()->create([
        'email_verified_at' => $verifiedAt,
        'invitation_token' => null,
        'invitation_sent_at' => null,
    ]);

    Auth::guard('web')->login($user);

    $user->refresh();
    expect($user->email_verified_at->toDateTimeString())->toBe($verifiedAt->toDateTimeString())
        ->and($user->invitation_token)->toBeNull();
});

test('saml email lookup is case insensitive', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'Jane.Doe@tue.nl',
        'invitation_token' => 'pending-token',
        'invitation_sent_at' => now(),
    ]);

    expect(User::findByEmailForSaml('jane.doe@tue.nl')?->id)->toBe($user->id)
        ->and(User::findByEmailForSaml('JANE.DOE@TUE.NL')?->id)->toBe($user->id);
});

test('admin sso login activates a pending invited user', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'researcher@tue.nl',
        'invitation_token' => 'pending-token',
        'invitation_sent_at' => now(),
        'surf_id' => null,
    ]);
    $user->assignRole('Researcher');

    $controller = app(App\Http\Controllers\Auth\SamlController::class);
    $method = new ReflectionMethod($controller, 'handleAdminAuth');

    $response = $method->invoke($controller, 'surf-persistent-id', 'Researcher@tue.nl', '/admin');

    $user->refresh();
    expect($response->getTargetUrl())->toEndWith('/admin')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->invitation_token)->toBeNull()
        ->and($user->surf_id)->toBe('surf-persistent-id');

    $this->assertAuthenticatedAs($user);
});
