<?php

use App\Models\ProjectSupervisor;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    seedTestData();
});

test('project detail route returns 200 for published projects', function () {
    $project = createProject();
    $project->update(['is_published' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertStatus(200);
    $response->assertSee($project->name);
});

test('project detail returns 404 for concept projects', function () {
    $project = createProject();
    $project->update(['is_published' => false]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertStatus(404);
});

test('project details are displayed', function () {
    $project = createProject();
    $project->update(['is_published' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertSee($project->name);
    $response->assertSee($project->short_description);
});

test('supervisor information is displayed', function () {
    $supervisor = createSupervisor();
    $project = createProject();
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisor->id,
        'order_rank' => 1,
    ]);
    $project->update(['is_published' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertSee($supervisor->name);
});

test('external supervisor information is displayed', function () {
    $project = createProject();
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => null,
        'supervisor_id' => null,
        'external_supervisor_name' => 'External Supervisor',
        'order_rank' => 1,
    ]);
    $project->update(['is_published' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertSee('External Supervisor');
});

test('tags are displayed', function () {
    $tag = Tag::factory()->create();
    $project = createProject();
    $project->tags()->attach($tag->id);
    $project->update(['is_published' => true]);

    $response = $this->get('/projects/'.$project->slug);

    $response->assertSee($tag->name);
});
