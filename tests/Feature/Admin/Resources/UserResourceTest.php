<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedTestData();
    $this->admin = authenticateAs(createUserWithRole('Administrator'));
});

test('user resource is admin-only', function () {
    $nonAdmin = authenticateAs(createSupervisor());

    $this->get('/admin/users')
        ->assertForbidden();
});

test('admin can access user resource', function () {
    $this->get('/admin/users')
        ->assertSuccessful();
});

test('can list users', function () {
    $user = User::factory()->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords([$user]);
});

test('can create user', function () {
    $group = createGroup();

    livewire(\App\Filament\Resources\Users\Pages\CreateUser::class)
        ->fillForm([
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'group_id' => $group->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

test('invite user warns about a similar account and waits for confirmation', function () {
    Notification::fake();

    $group = createGroup();
    User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'existing.person@tue.nl',
        'group_id' => $group->id,
    ]);

    livewire(ListUsers::class)
        ->mountAction('invite')
        ->assertMountedActionModalSee('Check before inviting')
        ->fillForm([
            'name' => 'Existing Person',
            'email' => 'new.person@example.com',
            'group_id' => $group->id,
            'roles' => ['Researcher'],
        ])
        ->assertMountedActionModalSee('existing.person@tue.nl')
        ->callMountedAction()
        ->assertHasFormErrors(['confirm_not_duplicate']);

    expect(User::where('email', 'new.person@example.com')->exists())->toBeFalse();

    livewire(ListUsers::class)
        ->callAction('invite', [
            'name' => 'Existing Person',
            'email' => 'new.person@example.com',
            'group_id' => $group->id,
            'roles' => ['Researcher'],
            'confirm_not_duplicate' => true,
        ])
        ->assertHasNoFormErrors();

    expect(User::where('email', 'new.person@example.com')->exists())->toBeTrue();
});

test('invite user rejects an email that differs only by casing', function () {
    Notification::fake();

    $group = createGroup();
    User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'Existing.Person@tue.nl',
        'group_id' => $group->id,
    ]);

    livewire(ListUsers::class)
        ->callAction('invite', [
            'name' => 'Existing Person',
            'email' => 'existing.person@tue.nl',
            'group_id' => $group->id,
            'roles' => ['Researcher'],
        ])
        ->assertHasFormErrors(['email']);

    expect(User::whereRaw('LOWER(email) = ?', ['existing.person@tue.nl'])->count())->toBe(1);
});
