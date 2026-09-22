<?php

beforeEach(function () {
    seedTestData();
});

test('help page includes the staff guide and the support contact', function () {
    $group = createGroup(['name' => 'Dynamics Group']);
    authenticateAs(createSupervisor(['group_id' => $group->id]));

    $this->get('/admin/help-and-contact')
        ->assertOk()
        ->assertSee('Using the admin panel')
        ->assertSee('https://studentprojects.wtb.tue.nl/admin', false)
        ->assertSee('Publish a project for students')
        ->assertSee('Invite new user')
        ->assertSee('Andreas Pollet');
});

test('help page explains the researcher supervisor rule', function () {
    $group = createGroup();
    authenticateAs(createUserWithRole('Researcher', ['group_id' => $group->id]));

    $this->get('/admin/help-and-contact')
        ->assertOk()
        ->assertSee('The project owner and the first supervisor must be a TU/e staff member')
        ->assertDontSee('Invite new user');
});

test('guests cannot open the help page', function () {
    $this->get('/admin/help-and-contact')
        ->assertRedirect('/admin/login');
});
