<?php

use App\Auth\StudentsUser;
use App\Models\User;

beforeEach(function () {
    seedTestData();
});

test('guests do not see the admin panel link', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('To admin panel')
        ->assertDontSee('Staff access');
});

test('staff signed in on the admin side see the admin panel link', function () {
    $admin = createUserWithRole('Administrator');

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee('To admin panel')
        ->assertSee('Staff access');
});

test('a public sign-in linked by surf id shows the admin panel link', function (string $role) {
    createUserWithRole($role, ['surf_id' => 'surf-staff']);

    $this->actingAs(new StudentsUser('surf-staff', 'employee', 'staff@tue.nl'), 'students')
        ->get('/')
        ->assertOk()
        ->assertSee('To admin panel')
        ->assertSee('Staff access');
})->with([
    'Administrator',
    'Staff member - supervisor',
    'Researcher',
    'Support colleague',
]);

test('a public sign-in whose surf id is not linked to staff does not see the admin panel link', function () {
    createUserWithRole('Administrator', ['surf_id' => 'surf-admin']);

    $this->actingAs(new StudentsUser('student-only', 'student', 'student@tue.nl'), 'students')
        ->get('/')
        ->assertOk()
        ->assertDontSee('To admin panel')
        ->assertDontSee('Staff access');
});

test('a surf identity linked to a user without panel access does not show the admin panel link', function () {
    User::factory()->create(['surf_id' => 'surf-no-role']);

    $this->actingAs(new StudentsUser('surf-no-role'), 'students')
        ->get('/')
        ->assertOk()
        ->assertDontSee('To admin panel')
        ->assertDontSee('Staff access');
});
