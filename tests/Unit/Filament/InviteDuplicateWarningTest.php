<?php

use App\Filament\Users\InviteDuplicateWarning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    seedTestData();
});

test('invite warning matches the same name and institutional local part', function () {
    $sameName = User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'someone.else@example.com',
    ]);
    $sameLocalPart = User::factory()->create([
        'name' => 'Student Account',
        'email' => 'a.jansen@student.tue.nl',
    ]);
    User::factory()->create([
        'name' => 'Unrelated User',
        'email' => 'unrelated@example.com',
    ]);

    $matches = InviteDuplicateWarning::matches('Existing Person', 'a.jansen@tue.nl');

    expect($matches->pluck('id')->all())->toEqualCanonicalizing([
        $sameName->id,
        $sameLocalPart->id,
    ]);
});

test('invite warning matches an email regardless of casing', function () {
    $sameEmail = User::factory()->create([
        'name' => 'Other Name',
        'email' => 'Existing.Person@tue.nl',
    ]);

    $matches = InviteDuplicateWarning::matches('New Person', 'existing.person@tue.nl');

    expect($matches->pluck('id')->all())->toBe([$sameEmail->id]);
});

test('invite warning ignores short names and plain local parts', function () {
    User::factory()->create([
        'name' => 'Al',
        'email' => 'admin@example.com',
    ]);

    expect(InviteDuplicateWarning::matches('Al', 'admin@tue.nl'))->toBeEmpty();
});

test('similar accounts exclude an exact email match', function () {
    $exact = User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'existing.person@tue.nl',
    ]);
    $sameName = User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'other.person@example.com',
    ]);

    $similar = InviteDuplicateWarning::similarAccounts('Existing Person', 'existing.person@tue.nl');

    expect($similar->pluck('id')->all())->toBe([$sameName->id])
        ->and(InviteDuplicateWarning::isSameEmail($exact, 'Existing.Person@tue.nl'))->toBeTrue();
});

test('invite is blocked until a similar account is confirmed', function () {
    User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'existing.person@tue.nl',
    ]);

    expect(InviteDuplicateWarning::blockReason([
        'name' => 'Existing Person',
        'email' => 'new.person@example.com',
    ]))->not->toBeNull()
        ->and(InviteDuplicateWarning::blockReason([
            'name' => 'Existing Person',
            'email' => 'new.person@example.com',
            'confirm_not_duplicate' => true,
        ]))->toBeNull()
        ->and(InviteDuplicateWarning::blockReason([
            'name' => 'Existing Person',
            'email' => 'existing.person@tue.nl',
            'confirm_not_duplicate' => true,
        ]))->toBe('Existing Person already uses this email address.');
});
