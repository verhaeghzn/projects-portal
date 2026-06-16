<?php

use App\Auth\StudentsUser;

beforeEach(function () {
    seedTestData();

    // Force SAML login to be required so the guest/authenticated distinction applies.
    config([
        'saml.enabled' => true,
        'saml.require_login' => true,
    ]);
});

test('guest only sees publicly published past projects', function () {
    $publicPast = createProject(['is_public' => true]);
    $publicPast->update(['student_name' => 'Jane Public']);

    $privatePast = createProject(['is_public' => false]);
    $privatePast->update(['student_name' => 'John Private']);

    $response = $this->get('/projects/past');

    $response->assertStatus(200);
    $response->assertSee($publicPast->name);
    $response->assertDontSee($privatePast->name);
});

test('authenticated student sees all past projects', function () {
    $publicPast = createProject(['is_public' => true]);
    $publicPast->update(['student_name' => 'Jane Public']);

    $privatePast = createProject(['is_public' => false]);
    $privatePast->update(['student_name' => 'John Private']);

    $response = $this->actingAs(new StudentsUser('pid-123'), 'students')
        ->get('/projects/past');

    $response->assertStatus(200);
    $response->assertSee($publicPast->name);
    $response->assertSee($privatePast->name);
});

test('guest can view a publicly published project detail page', function () {
    $project = createProject(['is_public' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertStatus(200);
    $response->assertSee($project->name);
});

test('guest is redirected to login for a non-public project detail page', function () {
    $project = createProject(['is_public' => false]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/saml/login');
});

test('authenticated student can view a non-public project detail page', function () {
    $project = createProject(['is_public' => false]);

    $response = $this->actingAs(new StudentsUser('pid-123'), 'students')
        ->get('/projects/'.$project->slug);

    $response->assertStatus(200);
    $response->assertSee($project->name);
});

test('non-public project detail page is open to everyone when login is not required', function () {
    config([
        'saml.enabled' => false,
        'saml.require_login' => false,
    ]);

    $project = createProject(['is_public' => false]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertStatus(200);
    $response->assertSee($project->name);
});
