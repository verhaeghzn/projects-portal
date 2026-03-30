<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\Section;
use App\Models\Tag;
use App\Models\TagCategory;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type');
        $natureTagSlug = $request->get('nature');
        $sectionSlug = $request->get('section');
        $focusTagSlug = $request->get('focus');
        $supervisorSlug = $request->get('supervisor');
        $groupId = $request->get('group');
        $supervisorName = null;

        $divisionSlug = $request->route('division');
        $selectedDivision = null;
        if ($divisionSlug) {
            $division = Division::where('slug', $divisionSlug)->with('sections')->first();
            if ($division) {
                $selectedDivision = [
                    'name' => $division->name,
                    'slug' => $division->slug,
                    'section_slugs' => $division->sections->pluck('slug')->all(),
                ];
            }
        }

        $query = Project::with(['supervisors', 'tags', 'owner', 'organization', 'types'])
            ->available()
            ->where('is_published', true);

        if ($selectedDivision && ! empty($selectedDivision['section_slugs'])) {
            $divisionSectionSlugs = $selectedDivision['section_slugs'];
            $query->whereHas('supervisorLinks', function ($q) use ($divisionSectionSlugs) {
                $q->where('supervisor_type', User::class)
                    ->whereIn('supervisor_id', function ($subQ) use ($divisionSectionSlugs) {
                        $subQ->select('users.id')
                            ->from('users')
                            ->join('groups', 'users.group_id', '=', 'groups.id')
                            ->join('sections', 'groups.section_id', '=', 'sections.id')
                            ->whereIn('sections.slug', $divisionSectionSlugs);
                    });
            });
        }

        if ($type) {
            $query->whereHas('types', function ($q) use ($type) {
                $q->where('project_types.slug', $type);
            });
        }

        if ($natureTagSlug) {
            $query->whereHas('tags', function ($q) use ($natureTagSlug) {
                $q->where('tags.slug', $natureTagSlug)
                    ->where('tags.category', TagCategory::Nature->value);
            });
        }

        if ($sectionSlug) {
            $query->whereHas('supervisorLinks', function ($q) use ($sectionSlug) {
                $q->where('supervisor_type', User::class)
                    ->whereIn('supervisor_id', function ($subQ) use ($sectionSlug) {
                        $subQ->select('users.id')
                            ->from('users')
                            ->join('groups', 'users.group_id', '=', 'groups.id')
                            ->join('sections', 'groups.section_id', '=', 'sections.id')
                            ->where('sections.slug', $sectionSlug);
                    });
            });
        }

        if ($focusTagSlug) {
            $query->whereHas('tags', function ($q) use ($focusTagSlug) {
                $q->where('tags.slug', $focusTagSlug)
                    ->where('tags.category', TagCategory::Focus->value);
            });
        }

        if ($supervisorSlug) {
            // Filter by internal supervisors only (User model)
            $matchingUserIds = User::where('slug', $supervisorSlug)->pluck('id');

            if ($matchingUserIds->isNotEmpty()) {
                // Filter projects that have a supervisor matching the slug
                $query->whereHas('supervisorLinks', function ($q) use ($matchingUserIds) {
                    $q->where('supervisor_type', User::class)
                        ->whereIn('supervisor_id', $matchingUserIds);
                });

                // Get supervisor name for display
                $supervisor = User::where('slug', $supervisorSlug)->first();
                if ($supervisor) {
                    $supervisorName = $supervisor->name;
                }
            }
        }

        if ($groupId) {
            $query->whereHas('supervisorLinks', function ($q) use ($groupId) {
                $q->where('supervisor_type', User::class)
                    ->whereIn('supervisor_id', function ($subQ) use ($groupId) {
                        $subQ->select('id')
                            ->from('users')
                            ->where('group_id', $groupId);
                    });
            });
        }

        $projects = $query->orderByRaw('COALESCE(random_ranking, 999999)')->get();

        // Get tags for filters
        $natureTags = Tag::where('category', TagCategory::Nature->value)
            ->orderBy('name')
            ->get();

        $sections = Section::with('division')->orderBy('name')
            ->get();

        if ($selectedDivision && ! empty($selectedDivision['section_slugs'])) {
            $sections = $sections->whereIn('slug', $selectedDivision['section_slugs'])->values();
        }

        $focusTags = Tag::where('category', TagCategory::Focus->value)
            ->orderBy('name')
            ->get();

        $groups = Group::with('section')
            ->orderBy('name')
            ->get();

        // Only get internal supervisors (User model)
        $supervisors = ProjectSupervisor::with(['supervisor.roles'])
            ->where('supervisor_type', User::class)
            ->whereNotNull('supervisor_id')
            ->get();
        $supervisors = $supervisors->map(function ($supervisor) {
            // Use the user's slug for internal supervisors
            $slug = $supervisor->supervisor?->slug ?? '';

            return [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'slug' => $slug,
                'type' => $supervisor->supervisor_type,
                'is_support_colleague' => $supervisor->supervisor?->hasRole('Support colleague') ?? false,
            ];
        })->filter(function ($supervisor) {
            // Filter out entries without a valid slug
            return ! empty($supervisor['slug'])
                && ! $supervisor['is_support_colleague'];
        })->map(function ($supervisor) {
            unset($supervisor['is_support_colleague']);

            return $supervisor;
        })->unique('slug')->values();

        return view('projects.index', [
            'projects' => $projects,
            'selectedType' => $type,
            'natureTags' => $natureTags,
            'sections' => $sections,
            'focusTags' => $focusTags,
            'groups' => $groups,
            'supervisors' => $supervisors,
            'selectedNature' => $natureTagSlug,
            'selectedSection' => $sectionSlug,
            'selectedFocus' => $focusTagSlug,
            'selectedSupervisor' => $supervisorSlug,
            'selectedSupervisorName' => $supervisorName,
            'selectedGroup' => $groupId,
            'selectedDivision' => $selectedDivision,
        ]);
    }

    public function past()
    {
        $projects = Project::with(['supervisors', 'tags', 'owner'])
            ->past()
            ->latest()
            ->paginate(12);

        return view('projects.past', [
            'projects' => $projects,
        ]);
    }

    public function show(Project $project)
    {
        // Only show published projects
        if (! $project->is_published) {
            abort(404);
        }

        $project->load([
            'supervisors.group.section',
            'tags',
            'owner.group.section',
            'organization',
            'types',
        ]);

        return view('projects.show', [
            'project' => $project,
        ]);
    }
}
