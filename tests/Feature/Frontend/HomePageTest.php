<?php

test('home route returns 200', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('home page renders correctly', function () {
    $response = $this->get('/');

    $response->assertViewIs('welcome');
});

test('home page shows ME portal title and three division cards', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Mechanical Engineering Projects Portal');
    $response->assertSee('Thermo-Fluids Engineering (TFE)');
    $response->assertSee('Computational and Experimental Mechanics (CEM)');
    $response->assertSee('Dynamical Systems Design (DSD)');
});

test('home page has view all projects of ME link', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('View all projects of ME');
    $response->assertSee(route('projects.index'), false);
});



