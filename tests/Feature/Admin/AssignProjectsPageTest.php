<?php

use App\Filament\Pages\AssignProjects;
use App\Models\Division;
use App\Models\ProjectSupervisor;
use App\Models\Section;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedTestData();
});

test('support colleague can access assign projects page for their division', function () {
    $division = Division::create(['name' => 'Division One']);
    $section = Section::factory()->create(['division_id' => $division->id]);
    $supportGroup = createGroup(['section_id' => $section->id]);
    $supportColleague = createUserWithRole('Support colleague', ['group_id' => $supportGroup->id]);

    authenticateAs($supportColleague);

    Livewire::test(AssignProjects::class)
        ->assertSuccessful();
});

test('staff supervisor cannot access assign projects page', function () {
    authenticateAs(createSupervisor());

    expect(AssignProjects::canAccess())->toBeFalse();
});

test('assign projects page lists only open published projects in the users division', function () {
    $division = Division::create(['name' => 'Division One']);
    $otherDivision = Division::create(['name' => 'Other Division']);

    $section = Section::factory()->create(['division_id' => $division->id]);
    $otherSection = Section::factory()->create(['division_id' => $otherDivision->id]);

    $supportGroup = createGroup(['section_id' => $section->id]);
    $supportColleague = createUserWithRole('Support colleague', ['group_id' => $supportGroup->id]);

    $projectSupervisor = createSupervisor();
    $projectSupervisor->update(['group_id' => createGroup(['section_id' => $section->id])->id]);

    $openProject = createProject([
        'project_owner_id' => $projectSupervisor->id,
        'is_published' => true,
    ]);
    $openProject->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $openProject->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $projectSupervisor->id,
        'order_rank' => 1,
    ]);

    $takenProject = createProject([
        'project_owner_id' => $projectSupervisor->id,
        'is_published' => true,
        'student_name' => 'Taken Student',
    ]);
    $takenProject->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $takenProject->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $projectSupervisor->id,
        'order_rank' => 1,
    ]);

    $otherDivisionSupervisor = createSupervisor();
    $otherDivisionSupervisor->update(['group_id' => createGroup(['section_id' => $otherSection->id])->id]);

    $otherDivisionProject = createProject([
        'project_owner_id' => $otherDivisionSupervisor->id,
        'is_published' => true,
    ]);
    $otherDivisionProject->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $otherDivisionProject->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $otherDivisionSupervisor->id,
        'order_rank' => 1,
    ]);

    authenticateAs($supportColleague);

    Livewire::test(AssignProjects::class)
        ->assertCanSeeTableRecords([$openProject])
        ->assertCanNotSeeTableRecords([$takenProject, $otherDivisionProject]);
});

test('support colleague can assign a student from the table', function () {
    $division = Division::create(['name' => 'Division One']);
    $section = Section::factory()->create(['division_id' => $division->id]);
    $supportGroup = createGroup(['section_id' => $section->id]);
    $supportColleague = createUserWithRole('Support colleague', ['group_id' => $supportGroup->id]);

    $projectSupervisor = createSupervisor();
    $projectSupervisor->update(['group_id' => createGroup(['section_id' => $section->id])->id]);

    $project = createProject([
        'project_owner_id' => $projectSupervisor->id,
        'is_published' => true,
    ]);
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $projectSupervisor->id,
        'order_rank' => 1,
    ]);

    authenticateAs($supportColleague);

    Livewire::test(AssignProjects::class)
        ->call('updateTableColumnState', 'student_name', (string) $project->getKey(), 'Jane Doe');

    $project->refresh();

    expect($project->student_name)->toBe('Jane Doe')
        ->and($project->is_taken)->toBeTrue();
});
