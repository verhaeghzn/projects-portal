<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\ProjectType;
use App\Models\Tag;
use App\Models\User;

beforeEach(function () {
    seedTestData();
});

test('project has owner relationship', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['project_owner_id' => $owner->id]);

    expect($project->owner->id)->toBe($owner->id);
});

test('project has supervisors relationship', function () {
    $supervisor = createSupervisor();
    $project = createProject();
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisor->id,
        'order_rank' => 1,
    ]);

    expect($project->fresh()->supervisors->first()->id)->toBe($supervisor->id);
});

test('project has tags relationship', function () {
    $tag = Tag::factory()->create();
    $project = createProject();
    $project->tags()->attach($tag->id);

    expect($project->tags->first()->id)->toBe($tag->id);
});

test('project has organization relationship', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    expect($project->organization->id)->toBe($organization->id);
});

test('project has types relationship', function () {
    $type = ProjectType::where('slug', 'bachelor_thesis')->first();
    $project = createProject();
    $project->types()->attach($type->id);

    expect($project->types->first()->id)->toBe($type->id);
});

test('available scope returns only available projects', function () {
    $available = Project::factory()->available()->create();
    $taken = Project::factory()->taken()->create();

    $availableProjects = Project::available()->get();

    expect($availableProjects->contains($available))->toBeTrue();
    expect($availableProjects->contains($taken))->toBeFalse();
});

test('past scope returns only past projects', function () {
    $available = Project::factory()->available()->create();
    $taken = Project::factory()->taken()->create();

    $pastProjects = Project::past()->get();

    expect($pastProjects->contains($taken))->toBeTrue();
    expect($pastProjects->contains($available))->toBeFalse();
});

test('is taken attribute returns true when student assigned', function () {
    $taken = Project::factory()->taken()->create();

    expect($taken->is_taken)->toBeTrue();
});

test('is taken attribute returns false when no student', function () {
    $available = Project::factory()->available()->create();

    expect($available->is_taken)->toBeFalse();
});

test('slug is auto-generated', function () {
    $project = Project::factory()->create(['name' => 'Test Project Name']);

    expect($project->slug)->toBe('test-project-name');
});

test('created_by_id is auto-set on creation', function () {
    $user = createSupervisor();
    $this->actingAs($user);

    $project = Project::factory()->create();

    expect($project->created_by_id)->toBe($user->id);
});

test('project validates first supervisor must be staff member', function () {
    $researcher = createUserWithRole('Researcher');
    $project = Project::factory()->make();

    // Save project first
    $project->save();

    // Add researcher as first supervisor (should fail validation)
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $researcher->id,
        'order_rank' => 1,
    ]);

    // Reload to get the supervisor link
    $project->load('supervisorLinks.supervisor.roles');

    // Attempting to save should throw validation exception
    expect(fn () => $project->save())->toThrow(\Illuminate\Validation\ValidationException::class);
});
