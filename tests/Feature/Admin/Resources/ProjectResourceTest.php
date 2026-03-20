<?php

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectType;

beforeEach(function () {
    seedTestData();
    $this->user = authenticateAs(createSupervisor());
});

test('list projects page can be rendered', function () {
    livewire(ListProjects::class)
        ->assertSuccessful();
});

test('can list projects', function () {
    $project = createProject();

    livewire(ListProjects::class)
        ->assertCanSeeTableRecords([$project]);
});

test('can filter projects by ownership', function () {
    $myProject = createProject(['project_owner_id' => $this->user->id]);
    $otherProject = createProject();

    livewire(ListProjects::class)
        ->filterTable('scopes', ['ownership' => 'my_projects'])
        ->assertCanSeeTableRecords([$myProject])
        ->assertCanNotSeeTableRecords([$otherProject]);
});

test('can filter projects by type', function () {
    $bachelorType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $masterType = ProjectType::where('slug', 'master_thesis')->first();

    $bachelorProject = createProject();
    $bachelorProject->types()->attach($bachelorType->id);

    $masterProject = createProject();
    $masterProject->types()->attach($masterType->id);

    livewire(ListProjects::class)
        ->filterTable('types', [$bachelorType->id])
        ->assertCanSeeTableRecords([$bachelorProject])
        ->assertCanNotSeeTableRecords([$masterProject]);
});

test('can filter projects by status', function () {
    $availableProject = createProject();
    $takenProject = createProject(['student_name' => 'John Doe']);
    $conceptProject = createProject();
    $conceptProject->update(['is_published' => false]);

    livewire(ListProjects::class)
        ->filterTable('status', ['status' => 'available'])
        ->assertCanSeeTableRecords([$availableProject])
        ->assertCanNotSeeTableRecords([$takenProject, $conceptProject]);
});

test('can search projects', function () {
    $project1 = createProject(['name' => 'Unique Project Name']);
    $project2 = createProject(['name' => 'Another Project']);

    livewire(ListProjects::class)
        ->searchTable('Unique Project Name')
        ->assertCanSeeTableRecords([$project1])
        ->assertCanNotSeeTableRecords([$project2]);
});

test('create project page can be rendered', function () {
    livewire(CreateProject::class)
        ->assertSuccessful();
});

test('can create project', function () {
    $supervisor = createSupervisor();
    $projectType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $organization = Organization::where('name', 'TU/e')->first() ?? Organization::factory()->create(['name' => 'TU/e']);

    livewire(CreateProject::class)
        ->fillForm([
            'name' => 'Test Project',
            'types' => [$projectType->id],
            'project_owner_id' => $supervisor->id,
            'organization_id' => $organization->id,
            'short_description' => 'Test description',
            'richtext_content' => 'Test content',
            'supervisorLinks' => [
                [
                    'supervisor_type_selector' => 'internal',
                    'supervisor_id' => $supervisor->id,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::where('name', 'Test Project')->exists())->toBeTrue();
});

test('can create project as draft via is_published checkbox', function () {
    $supervisor = createSupervisor();
    $projectType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $organization = Organization::where('name', 'TU/e')->first() ?? Organization::factory()->create(['name' => 'TU/e']);

    livewire(CreateProject::class)
        ->fillForm([
            'name' => 'Draft Project',
            'types' => [$projectType->id],
            'project_owner_id' => $supervisor->id,
            'organization_id' => $organization->id,
            'short_description' => 'Test description',
            'richtext_content' => 'Test content',
            'supervisorLinks' => [
                [
                    'supervisor_type_selector' => 'internal',
                    'supervisor_id' => $supervisor->id,
                ],
            ],
            'is_published' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('name', 'Draft Project')->first();
    expect($project->is_published)->toBeFalse();
});

test('project number is auto-generated after save', function () {
    $supervisor = createSupervisor();
    $section = createSection(['abbrev_id' => 'SEC']);
    $group = createGroup(['section_id' => $section->id, 'abbrev_id' => 'GRP']);
    $supervisor->update(['group_id' => $group->id]);

    $projectType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $organization = Organization::where('name', 'TU/e')->first() ?? Organization::factory()->create(['name' => 'TU/e']);

    livewire(CreateProject::class)
        ->fillForm([
            'name' => 'Auto Number Project',
            'types' => [$projectType->id],
            'project_owner_id' => $supervisor->id,
            'organization_id' => $organization->id,
            'short_description' => 'Test description',
            'richtext_content' => 'Test content',
            'supervisorLinks' => [
                [
                    'supervisor_type_selector' => 'internal',
                    'supervisor_id' => $supervisor->id,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $project = Project::where('name', 'Auto Number Project')->first();
    expect($project->project_number)->not->toBeNull();
});

test('first supervisor must be staff member', function () {
    $researcher = createUserWithRole('Researcher');
    $projectType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $organization = Organization::where('name', 'TU/e')->first() ?? Organization::factory()->create(['name' => 'TU/e']);

    livewire(CreateProject::class)
        ->fillForm([
            'name' => 'Invalid Project',
            'types' => [$projectType->id],
            'project_owner_id' => $this->user->id,
            'organization_id' => $organization->id,
            'short_description' => 'Test description',
            'richtext_content' => 'Test content',
            'supervisorLinks' => [
                [
                    'supervisor_type_selector' => 'internal',
                    'supervisor_id' => $researcher->id, // Researcher, not staff supervisor
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['supervisorLinks']);
});

test('edit project page can be rendered', function () {
    $project = createProject();

    livewire(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertSuccessful();
});

test('can edit project', function () {
    $project = createProject();

    livewire(EditProject::class, ['record' => $project->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Project Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($project->fresh()->name)->toBe('Updated Project Name');
});

test('can delete project as admin', function () {
    $admin = authenticateAs(createUserWithRole('Administrator'));
    $project = createProject();

    livewire(EditProject::class, ['record' => $project->getRouteKey()])
        ->callAction('delete')
        ->assertSuccessful();

    expect(Project::find($project->id))->toBeNull();
});

test('non-admin cannot delete project', function () {
    $project = createProject();

    livewire(EditProject::class, ['record' => $project->getRouteKey()])
        ->assertActionHidden('delete');
});
