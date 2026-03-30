<?php

use App\Models\Group;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\ProjectType;
use App\Models\Section;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Create a user with a specific role
 */
function createUserWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $roleModel = Role::firstOrCreate(['name' => $role]);
    $user->assignRole($roleModel);

    return $user;
}

/**
 * Create a staff supervisor user
 */
function createSupervisor(array $attributes = []): User
{
    return createUserWithRole('Staff member - supervisor', $attributes);
}

/**
 * Create a section
 */
function createSection(array $attributes = []): Section
{
    return Section::factory()->create($attributes);
}

/**
 * Create a group
 */
function createGroup(array $attributes = []): Group
{
    if (!isset($attributes['section_id'])) {
        $section = createSection();
        $attributes['section_id'] = $section->id;
    }

    return Group::factory()->create($attributes);
}

/**
 * Create an organization
 */
function createOrganization(array $attributes = []): Organization
{
    return Organization::factory()->create($attributes);
}

/**
 * Create a project with default relationships
 */
function createProject(array $attributes = []): Project
{
    // Ensure we have a supervisor (required for project validation)
    $supervisor = createSupervisor();
    $group = $supervisor->group ?? createGroup();
    if (!$supervisor->group_id) {
        $supervisor->update(['group_id' => $group->id]);
    }

    // Set default owner if not provided
    if (!isset($attributes['project_owner_id'])) {
        $attributes['project_owner_id'] = $supervisor->id;
    }

    $project = Project::factory()->create($attributes);
    
    // Create supervisor link if not already set
    if (!isset($attributes['supervisorLinks'])) {
        ProjectSupervisor::create([
            'project_id' => $project->id,
            'supervisor_type' => User::class,
            'supervisor_id' => $supervisor->id,
            'order_rank' => 1,
        ]);
    }

    // Reload to get relationships
    return $project->fresh(['supervisorLinks', 'supervisors', 'owner']);
}

/**
 * Authenticate as a user
 */
function authenticateAs(User $user): User
{
    test()->actingAs($user);

    return $user;
}

/**
 * Seed common test data (roles, project types, etc.)
 */
function seedTestData(): void
{
    // Create roles
    Role::firstOrCreate(['name' => 'Administrator']);
    Role::firstOrCreate(['name' => 'Staff member - supervisor']);
    Role::firstOrCreate(['name' => 'Researcher']);
    Role::firstOrCreate(['name' => 'Support colleague']);

    // Create project types
    ProjectType::firstOrCreate(
        ['slug' => 'bachelor_thesis'],
        ['name' => 'Bachelor Thesis Project']
    );
    ProjectType::firstOrCreate(
        ['slug' => 'master_thesis'],
        ['name' => 'Master Thesis Project']
    );
}

