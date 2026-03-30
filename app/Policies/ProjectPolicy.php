<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Staff member - supervisor', 'Researcher', 'Support colleague']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        return $user->hasAnyRole(['Administrator', 'Staff member - supervisor', 'Researcher', 'Support colleague']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrator', 'Staff member - supervisor', 'Researcher', 'Support colleague']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Administrators can update all projects
        if ($user->hasRole('Administrator')) {
            return true;
        }

        // Creators can update their own projects
        if ($project->created_by_id === $user->id) {
            return true;
        }

        // Researchers can update projects owned by their group leader or projects they supervise
        if ($user->hasRole('Researcher')) {           
            // Check if user is a supervisor of the project
            return $project->supervisorLinks()->where('supervisor_id', $user->id)->exists();
        }

        // Staff member - supervisors can update their own projects or projects with supervisors in groups they lead
        if ($user->hasRole('Staff member - supervisor')) {
            // Check if they own the project
            if ($project->project_owner_id === $user->id) {
                return true;
            }
            
            // Check if at least one supervisor is in a group they lead
            $groupsLedByUser = \App\Models\Group::where('group_leader_id', $user->id)->pluck('id');
            if ($groupsLedByUser->isNotEmpty()) {
                // Load supervisors and check if any are in groups led by this user
                $project->load('supervisorLinks.supervisor.group');
                foreach ($project->supervisorLinks as $supervisorLink) {
                    $supervisor = $supervisorLink->supervisor;
                    // Only check User supervisors (not external supervisors)
                    if ($supervisor instanceof User && $supervisor->group_id && $groupsLedByUser->contains($supervisor->group_id)) {
                        return true;
                    }
                }
            }
        }

        // Support colleagues can update projects within their division.
        if ($user->hasRole('Support colleague')) {
            return $this->sharesDivisionWithProject($user, $project);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Administrators can delete all projects
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->hasRole('Support colleague')) {
            return $this->sharesDivisionWithProject($user, $project);
        }

        // Staff member - supervisors can delete their own projects
        if ($user->hasRole('Staff member - supervisor') && $project->project_owner_id === $user->id) {
            return true;
        }

        // Creators can delete their own projects
        if ($user->hasRole('Researcher') && $project->created_by_id === $user->id) {
            return true;
        }

        return false;
    }

    private function sharesDivisionWithProject(User $user, Project $project): bool
    {
        $userDivisionId = $this->getUserDivisionId($user);
        if (! $userDivisionId) {
            return false;
        }

        return $this->getProjectDivisionIds($project)->contains($userDivisionId);
    }

    private function getUserDivisionId(User $user): ?int
    {
        return $user->group?->section?->division_id;
    }

    private function getProjectDivisionIds(Project $project): Collection
    {
        $project->loadMissing([
            'owner.group.section',
            'creator.group.section',
            'supervisorLinks.supervisor.group.section',
        ]);

        $divisionIds = collect();

        if ($project->owner?->group?->section?->division_id) {
            $divisionIds->push($project->owner->group->section->division_id);
        }

        if ($project->creator?->group?->section?->division_id) {
            $divisionIds->push($project->creator->group->section->division_id);
        }

        foreach ($project->supervisorLinks as $supervisorLink) {
            $supervisor = $supervisorLink->supervisor;
            if ($supervisor instanceof User && $supervisor->group?->section?->division_id) {
                $divisionIds->push($supervisor->group->section->division_id);
            }
        }

        return $divisionIds->filter()->unique()->values();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        return $user->hasRole('Administrator');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasRole('Administrator');
    }
}
