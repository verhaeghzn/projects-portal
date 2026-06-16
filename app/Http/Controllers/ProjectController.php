<?php

namespace App\Http\Controllers;

use App\Models\Division;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\Section;
use App\Models\User;
use App\Services\ProjectSmartSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ProjectController extends Controller
{
    public function index(Request $request, ProjectSmartSearchService $smartSearch)
    {
        $type = $request->get('type');
        $sectionSlug = $request->get('section');
        $supervisorSlug = $request->get('supervisor');
        $groupId = $request->get('group');
        $supervisorName = null;
        $searchQuery = trim((string) $request->query('q', ''));
        $hasManualFilters = $type || $sectionSlug || $supervisorSlug || $groupId;
        $manualBrowse = $request->boolean('browse') || ($searchQuery === '' && $hasManualFilters);

        $divisionSlug = $request->route('division');
        $selectedDivision = null;
        if ($divisionSlug) {
            $division = Division::where('slug', $divisionSlug)->with('sections')->first();
            if ($division) {
                $selectedDivision = [
                    'name' => $division->name,
                    'abbrev' => $division->abbrev,
                    'slug' => $division->slug,
                    'section_slugs' => $division->sections->pluck('slug')->all(),
                ];
            }
        }

        $shouldLoadProjects = $manualBrowse || $searchQuery !== '';

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

        $smartSearchSummary = null;
        $smartSearchError = null;
        $smartSearchDebug = null;
        $smartSearchCriteria = null;
        $selectedThesisType = in_array($type, ['bachelor_thesis', 'master_thesis'], true)
            ? $type
            : 'master_thesis';

        if ($shouldLoadProjects && $searchQuery !== '') {
            if (! $type || ! in_array($type, ['bachelor_thesis', 'master_thesis'], true)) {
                $query->whereHas('types', function ($q) use ($selectedThesisType) {
                    $q->where('project_types.slug', $selectedThesisType);
                });
            }

            $candidates = (clone $query)->with([
                'types',
                'organization',
                'supervisorLinks.supervisor.group.section',
            ])->get();

            $rateKey = 'ai-project-search:'.$request->ip();
            $interpreted = RateLimiter::attempt(
                $rateKey,
                20,
                fn () => $smartSearch->interpret(
                    $searchQuery,
                    $selectedDivision,
                    $selectedThesisType,
                    $candidates,
                    [
                        'type' => $type,
                        'section' => $sectionSlug,
                        'supervisor' => $supervisorSlug,
                        'group' => $groupId,
                    ],
                ),
                60
            );

            if ($interpreted === false) {
                $smartSearchError = 'Too many smart searches from your network. Please wait a minute and try again.';
            } else {
                $smartSearchSummary = $interpreted['summary'];
                $smartSearchError = $interpreted['error'];
                $smartSearchDebug = $interpreted['debug'];
                $smartSearchCriteria = $interpreted['criteria'];
                if ($interpreted['criteria'] !== null) {
                    $smartSearch->applyToQuery($query, $interpreted['criteria']);
                }
            }
        }

        $projects = $shouldLoadProjects
            ? $query->orderByRaw('COALESCE(random_ranking, 999999)')->get()
            : collect();

        if ($smartSearchCriteria !== null && ! $smartSearchCriteria->isEmpty()) {
            $projects = $smartSearch->orderProjects($projects, $smartSearchCriteria);
        }

        $sections = collect();
        $groups = collect();
        $supervisors = collect();

        if ($manualBrowse) {
            $sections = Section::with('division')->orderBy('name')
                ->get();

            if ($selectedDivision && ! empty($selectedDivision['section_slugs'])) {
                $sections = $sections->whereIn('slug', $selectedDivision['section_slugs'])->values();
            }

            $groups = Group::with('section')
                ->orderBy('name')
                ->get();

            $supervisorsQuery = ProjectSupervisor::with(['supervisor.roles'])
                ->where('supervisor_type', User::class)
                ->whereNotNull('supervisor_id');

            if ($selectedDivision && ! empty($selectedDivision['section_slugs'])) {
                $divisionSectionSlugs = $selectedDivision['section_slugs'];
                $supervisorsQuery->whereIn('supervisor_id', function ($subQ) use ($divisionSectionSlugs) {
                    $subQ->select('users.id')
                        ->from('users')
                        ->join('groups', 'users.group_id', '=', 'groups.id')
                        ->join('sections', 'groups.section_id', '=', 'sections.id')
                        ->whereIn('sections.slug', $divisionSectionSlugs);
                });
            }

            $supervisors = $supervisorsQuery->get()
                ->map(function ($supervisor) {
                    $slug = $supervisor->supervisor?->slug ?? '';

                    return [
                        'id' => $supervisor->id,
                        'name' => $supervisor->name,
                        'slug' => $slug,
                        'type' => $supervisor->supervisor_type,
                        'is_support_colleague' => $supervisor->supervisor?->hasRole('Support colleague') ?? false,
                    ];
                })
                ->filter(fn ($supervisor) => ! empty($supervisor['slug']) && ! $supervisor['is_support_colleague'])
                ->map(function ($supervisor) {
                    unset($supervisor['is_support_colleague']);

                    return $supervisor;
                })
                ->unique('slug')
                ->values();
        }

        $manualBrowseUrl = $divisionSlug
            ? route('projects.division.'.$divisionSlug, ['browse' => 1])
            : route('projects.index', ['browse' => 1]);

        return view('projects.index', [
            'projects' => $projects,
            'selectedType' => $type,
            'sections' => $sections,
            'groups' => $groups,
            'supervisors' => $supervisors,
            'selectedSection' => $sectionSlug,
            'selectedSupervisor' => $supervisorSlug,
            'selectedSupervisorName' => $supervisorName,
            'selectedGroup' => $groupId,
            'selectedDivision' => $selectedDivision,
            'searchQuery' => $searchQuery,
            'smartSearchSummary' => $smartSearchSummary,
            'smartSearchError' => $smartSearchError,
            'smartSearchDebug' => $smartSearchDebug,
            'selectedThesisType' => $selectedThesisType,
            'manualBrowse' => $manualBrowse,
            'manualBrowseUrl' => $manualBrowseUrl,
            'hideFooter' => ! $manualBrowse,
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
