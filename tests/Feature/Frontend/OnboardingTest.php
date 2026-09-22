<?php

use App\Models\Division;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    seedTestData();
    Storage::fake('public');
});

test('onboarding shows form for valid token', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'valid-token',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->get('/onboarding/valid-token');

    $response->assertStatus(200);
    $response->assertSee($user->name);
});

test('onboarding prefills group when user already has one', function () {
    $group = createGroup(['name' => 'Assigned Research Group']);
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'valid-token',
        'invitation_sent_at' => now(),
        'group_id' => $group->id,
    ]);

    $response = $this->get('/onboarding/valid-token');

    $response->assertOk();
    $response->assertSee('Assigned Research Group', false);
    $response->assertSee('value="'.$group->id.'" selected', false);
    $response->assertSee('name="group_id" value="'.$user->group_id.'"', false);
});

test('onboarding keeps existing group when group field is not submitted', function () {
    $group = createGroup();
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
        'group_id' => $group->id,
    ]);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ]);

    $response->assertRedirect(route('onboarding.welcome'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->group_id)->toBe($group->id);
    expect($user->email_verified_at)->not->toBeNull();
});

test('onboarding returns 404 for invalid token', function () {
    $response = $this->get('/onboarding/invalid-token');

    $response->assertStatus(404);
});

test('onboarding returns 410 for expired token', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'expired-token',
        'invitation_sent_at' => now()->subDays(8), // 8 days ago (expired)
    ]);

    $response = $this->get('/onboarding/expired-token');

    $response->assertStatus(410);
});

test('onboarding form validates password', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'short',
        'password_confirmation' => 'short',
        'group_id' => createGroup()->id,
    ]);

    $response->assertSessionHasErrors('password');
});

test('onboarding form validates password confirmation', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword123!',
        'group_id' => createGroup()->id,
    ]);

    $response->assertSessionHasErrors('password');
});

test('onboarding form validates group_id', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'group_id' => 99999, // Non-existent group
    ]);

    $response->assertSessionHasErrors('group_id');
});

test('onboarding successfully sets up user', function () {
    $group = createGroup();
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => $group->id,
    ]);

    $response->assertRedirect(route('onboarding.welcome'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->invitation_token)->toBeNull();
    expect($user->group_id)->toBe($group->id);
    expect(Hash::check('NewPassword123!', $user->password))->toBeTrue();
});

test('onboarding handles avatar upload', function () {
    $group = createGroup();
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => $group->id,
        'avatar' => $avatar,
    ]);

    $response->assertRedirect(route('onboarding.welcome'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->avatar_url)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar_url);
});

test('onboarding validates avatar file type', function () {
    $group = createGroup();
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

    $response = $this->post('/onboarding/test-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => $group->id,
        'avatar' => $invalidFile,
    ]);

    $response->assertSessionHasErrors('avatar');
});

test('onboarding validates avatar file size', function () {
    $group = createGroup();
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'test-token',
        'invitation_sent_at' => now(),
    ]);

    $largeFile = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB, exceeds 2MB limit

    $response = $this->post('/onboarding/test-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => $group->id,
        'avatar' => $largeFile,
    ]);

    $response->assertSessionHasErrors('avatar');
});

test('onboarding handles expired token on POST', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'expired-token',
        'invitation_sent_at' => now()->subDays(8),
    ]);

    $response = $this->post('/onboarding/expired-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => createGroup()->id,
    ]);

    $response->assertSessionHasErrors('token');
});

test('onboarding signs the user in so they can open the admin panel', function () {
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'admin-access-token',
        'invitation_sent_at' => now(),
        'name' => 'Casey Researcher',
    ]);
    $user->assignRole('Researcher');

    $this->post('/onboarding/admin-access-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => createGroup()->id,
    ])->assertRedirect(route('onboarding.welcome'));

    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Casey Researcher');
});

test('onboarding replaces an existing session so the new user stays signed in', function () {
    $previous = createUserWithRole('Staff member - supervisor');
    $this->actingAs($previous);

    $user = User::factory()->unverified()->create([
        'invitation_token' => 'switch-user-token',
        'invitation_sent_at' => now(),
        'name' => 'Jordan Researcher',
    ]);
    $user->assignRole('Researcher');

    $this->post('/onboarding/switch-user-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
        'group_id' => createGroup()->id,
    ])->assertRedirect(route('onboarding.welcome'));

    $this->assertAuthenticatedAs($user);

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Jordan Researcher')
        ->assertDontSee($previous->name);
});

test('welcome page redirects guests to the admin login', function () {
    $this->get(route('onboarding.welcome'))
        ->assertRedirect('/admin/login');
});

test('onboarding welcome is tailored for researchers', function () {
    $group = createGroup(['name' => 'Energy Systems']);
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'researcher-token',
        'invitation_sent_at' => now(),
        'name' => 'Ada Researcher',
        'group_id' => $group->id,
    ]);
    $user->assignRole('Researcher');

    $this->post('/onboarding/researcher-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('onboarding.welcome'));

    $this->get(route('onboarding.welcome'))
        ->assertOk()
        ->assertSee('Welcome, Ada')
        ->assertSee('researcher', false)
        ->assertSee('Bookmark the admin panel')
        ->assertSee('https://studentprojects.wtb.tue.nl/admin', false)
        ->assertSee('Energy Systems')
        ->assertSee('The project owner and the first supervisor must be a TU/e staff member')
        ->assertDontSee('Use Invite new user')
        ->assertDontSee('Your group')
        ->assertDontSee('How students find your project')
        ->assertSee('Configure "sign in with TU/e account"', false)
        ->assertSee('Continue without TU/e account', false)
        ->assertSee(route('saml.link', ['return' => url('/admin')]), false);
});

test('onboarding welcome is tailored for staff members', function () {
    $group = createGroup(['name' => 'Dynamics Group']);
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'staff-token',
        'invitation_sent_at' => now(),
        'name' => 'Sam Staff',
        'group_id' => $group->id,
    ]);
    $user->assignRole('Staff member - supervisor');

    $this->post('/onboarding/staff-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('onboarding.welcome'));

    $this->get(route('onboarding.welcome'))
        ->assertOk()
        ->assertSee('Welcome, Sam')
        ->assertSee('staff member', false)
        ->assertSee('Bookmark the admin panel')
        ->assertSee('https://studentprojects.wtb.tue.nl/admin', false)
        ->assertSee('Dynamics Group')
        ->assertSee('Invite new user')
        ->assertDontSee('How students find your project')
        ->assertSee('Configure "sign in with TU/e account"', false)
        ->assertSee('Continue without TU/e account', false);
});

test('onboarding welcome is tailored for support colleagues', function () {
    $division = Division::create(['name' => 'Thermo-Fluids Engineering']);
    $section = createSection(['division_id' => $division->id]);
    $group = createGroup(['section_id' => $section->id]);
    $user = User::factory()->unverified()->create([
        'invitation_token' => 'support-token',
        'invitation_sent_at' => now(),
        'name' => 'Pat Support',
        'group_id' => $group->id,
    ]);
    $user->assignRole('Support colleague');

    $this->post('/onboarding/support-token', [
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect(route('onboarding.welcome'));

    $this->get(route('onboarding.welcome'))
        ->assertOk()
        ->assertSee('Welcome, Pat')
        ->assertSee('support colleague', false)
        ->assertSee('Bookmark the admin panel')
        ->assertSee('https://studentprojects.wtb.tue.nl/admin', false)
        ->assertSee('Assign projects')
        ->assertDontSee('Your group')
        ->assertDontSee('How students find your project')
        ->assertSee('Continue without TU/e account', false);
});
