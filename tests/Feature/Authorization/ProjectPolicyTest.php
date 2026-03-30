<?php

use App\Models\Division;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\Section;
use App\Models\User;

beforeEach(function () {
    seedTestData();
});

test('all roles can view any projects', function () {
    $project = createProject();
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $researcher = createUserWithRole('Researcher');
    $supportColleague = createUserWithRole('Support colleague');

    expect($admin->can('viewAny', Project::class))->toBeTrue();
    expect($supervisor->can('viewAny', Project::class))->toBeTrue();
    expect($researcher->can('viewAny', Project::class))->toBeTrue();
    expect($supportColleague->can('viewAny', Project::class))->toBeTrue();
});

test('all roles can view individual projects', function () {
    $project = createProject();
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $researcher = createUserWithRole('Researcher');
    $supportColleague = createUserWithRole('Support colleague');

    expect($admin->can('view', $project))->toBeTrue();
    expect($supervisor->can('view', $project))->toBeTrue();
    expect($researcher->can('view', $project))->toBeTrue();
    expect($supportColleague->can('view', $project))->toBeTrue();
});

test('all roles can create projects', function () {
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $researcher = createUserWithRole('Researcher');
    $supportColleague = createUserWithRole('Support colleague');

    expect($admin->can('create', Project::class))->toBeTrue();
    expect($supervisor->can('create', Project::class))->toBeTrue();
    expect($researcher->can('create', Project::class))->toBeTrue();
    expect($supportColleague->can('create', Project::class))->toBeTrue();
});

test('administrator can update all projects', function () {
    $admin = createUserWithRole('Administrator');
    $project = createProject();

    expect($admin->can('update', $project))->toBeTrue();
});

test('staff supervisor can update own projects', function () {
    $supervisor = createSupervisor();
    $ownProject = createProject(['project_owner_id' => $supervisor->id]);
    $otherProject = createProject();

    expect($supervisor->can('update', $ownProject))->toBeTrue();
    // Should also be able to update if they supervise it
    $otherProject->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $otherProject->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisor->id,
        'order_rank' => 1,
    ]);
    expect($supervisor->can('update', $otherProject->fresh()))->toBeTrue();
});

test('researcher can update projects they supervise', function () {
    $researcher = createUserWithRole('Researcher');
    $project = createProject();
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $researcher->id,
        'order_rank' => 1,
    ]);

    expect($researcher->can('update', $project->fresh()))->toBeTrue();
});

test('researcher can update projects owned by group leader', function () {
    $groupLeader = createSupervisor();
    $group = createGroup(['group_leader_id' => $groupLeader->id]);
    $researcher = createUserWithRole('Researcher', ['group_id' => $group->id]);
    
    $project = createProject(['project_owner_id' => $groupLeader->id]);

    expect($researcher->can('update', $project))->toBeTrue();
});

test('administrator and in-division support colleague can delete projects', function () {
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $researcher = createUserWithRole('Researcher');
    $supportColleague = createUserWithRole('Support colleague');
    $project = createProject();

    expect($admin->can('delete', $project))->toBeTrue();
    expect($supervisor->can('delete', $project))->toBeFalse();
    expect($researcher->can('delete', $project))->toBeFalse();
    expect($supportColleague->can('delete', $project))->toBeFalse();
});

test('support colleague can update and delete projects in their division', function () {
    $division = Division::create(['name' => 'Division One']);
    $section = Section::factory()->create(['division_id' => $division->id]);
    $supportGroup = createGroup(['section_id' => $section->id]);
    $supportColleague = createUserWithRole('Support colleague', ['group_id' => $supportGroup->id]);

    $projectSupervisor = createSupervisor();
    $projectSupervisorGroup = createGroup(['section_id' => $section->id]);
    $projectSupervisor->update(['group_id' => $projectSupervisorGroup->id]);

    $project = createProject(['project_owner_id' => $projectSupervisor->id]);
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $projectSupervisor->id,
        'order_rank' => 1,
    ]);

    $freshProject = $project->fresh();

    expect($supportColleague->can('update', $freshProject))->toBeTrue();
    expect($supportColleague->can('delete', $freshProject))->toBeTrue();
});

test('support colleague cannot update or delete projects outside their division', function () {
    $supportDivision = Division::create(['name' => 'Support Division']);
    $otherDivision = Division::create(['name' => 'Other Division']);

    $supportSection = Section::factory()->create(['division_id' => $supportDivision->id]);
    $otherSection = Section::factory()->create(['division_id' => $otherDivision->id]);

    $supportGroup = createGroup(['section_id' => $supportSection->id]);
    $supportColleague = createUserWithRole('Support colleague', ['group_id' => $supportGroup->id]);

    $projectSupervisor = createSupervisor();
    $projectSupervisorGroup = createGroup(['section_id' => $otherSection->id]);
    $projectSupervisor->update(['group_id' => $projectSupervisorGroup->id]);

    $project = createProject(['project_owner_id' => $projectSupervisor->id]);
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $projectSupervisor->id,
        'order_rank' => 1,
    ]);

    $freshProject = $project->fresh();

    expect($supportColleague->can('update', $freshProject))->toBeFalse();
    expect($supportColleague->can('delete', $freshProject))->toBeFalse();
});

test('only administrator can restore projects', function () {
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $project = createProject();

    expect($admin->can('restore', $project))->toBeTrue();
    expect($supervisor->can('restore', $project))->toBeFalse();
});

test('only administrator can force delete projects', function () {
    $admin = createUserWithRole('Administrator');
    $supervisor = createSupervisor();
    $project = createProject();

    expect($admin->can('forceDelete', $project))->toBeTrue();
    expect($supervisor->can('forceDelete', $project))->toBeFalse();
});



