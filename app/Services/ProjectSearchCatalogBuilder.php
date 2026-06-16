<?php

namespace App\Services;

use App\Models\Division;
use App\Models\Group;
use App\Models\Project;
use App\Models\TagCategory;
use App\Models\User;
use Illuminate\Support\Collection;

class ProjectSearchCatalogBuilder
{
    /**
     * Build the smart-search prompt payload from a pre-filtered candidate pool.
     *
     * @param  Collection<int, Project>  $candidates
     * @param  array{name: string, slug: string, section_slugs: list<string>}|null  $selectedDivision
     * @return array<string, mixed>
     */
    public function buildFromCandidates(Collection $candidates, ?array $selectedDivision, ?string $thesisType): array
    {
        $maxProjects = (int) config('ai.project_search.max_projects', 100);
        $totalCandidates = $candidates->count();
        $truncated = $totalCandidates > $maxProjects;

        if ($truncated) {
            $candidates = $candidates->take($maxProjects);
        }

        return [
            'context' => [
                'division' => $selectedDivision !== null
                    ? ['name' => $selectedDivision['name'], 'slug' => $selectedDivision['slug']]
                    : null,
                'project_type_filter' => $thesisType,
                'candidate_count' => $totalCandidates,
                'included_count' => $candidates->count(),
                'truncated' => $truncated,
            ],
            'groups' => $this->groupEntries($candidates),
            'projects' => $candidates
                ->map(fn (Project $project) => $this->projectEntry($project))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array{name: string, slug: string, section_slugs: list<string>}|null  $selectedDivision
     */
    public static function selectedDivisionFromRequest(?string $divisionSlug): ?array
    {
        if (! $divisionSlug) {
            return null;
        }

        $division = Division::query()->where('slug', $divisionSlug)->with('sections')->first();
        if (! $division) {
            return null;
        }

        return [
            'name' => $division->name,
            'abbrev' => $division->abbrev,
            'slug' => $division->slug,
            'section_slugs' => $division->sections->pluck('slug')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projectEntry(Project $project): array
    {
        $supervisorGroups = $this->supervisorGroups($project);
        $primaryGroupLink = $project->supervisorLinks
            ->first(fn ($link) => $link->supervisor_type === User::class && $link->supervisor?->group);
        $section = $primaryGroupLink?->supervisor?->group?->section;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'short_description' => $project->short_description,
            'summary' => $this->catalogSummary($project),
            'types' => $project->types->pluck('slug')->values()->all(),
            'nature_tags' => $project->tags
                ->where('category', TagCategory::Nature)
                ->pluck('name')
                ->values()
                ->all(),
            'focus_tags' => $project->tags
                ->where('category', TagCategory::Focus)
                ->pluck('name')
                ->values()
                ->all(),
            'supervisors' => $project->supervisorLinks
                ->map(fn ($link) => $link->supervisor?->slug)
                ->filter()
                ->values()
                ->all(),
            'section' => $section !== null
                ? ['slug' => $section->slug, 'name' => $section->name]
                : null,
            'group' => $supervisorGroups[0] ?? null,
            'groups' => $supervisorGroups,
            'organization' => $project->organization?->name,
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function supervisorGroups(Project $project): array
    {
        $groups = [];
        $seen = [];

        foreach ($project->supervisorLinks as $link) {
            if ($link->supervisor_type !== User::class || ! $link->supervisor?->group) {
                continue;
            }

            $group = $link->supervisor->group;
            if (isset($seen[$group->id])) {
                continue;
            }

            $seen[$group->id] = true;
            $groups[] = ['id' => $group->id, 'name' => $group->name];
        }

        return $groups;
    }

    /**
     * @param  Collection<int, Project>  $candidates
     * @return list<array<string, mixed>>
     */
    private function groupEntries(Collection $candidates): array
    {
        $groupIds = $candidates
            ->flatMap(fn (Project $project) => collect($this->supervisorGroups($project))->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        if ($groupIds->isEmpty()) {
            return [];
        }

        return Group::query()
            ->with('section')
            ->whereIn('id', $groupIds)
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'section' => $group->section !== null
                    ? ['slug' => $group->section->slug, 'name' => $group->section->name]
                    : null,
                'description' => filled($group->search_summary) ? $group->search_summary : null,
            ])
            ->values()
            ->all();
    }

    private function catalogSummary(Project $project): string
    {
        $summary = filled($project->search_summary)
            ? $project->search_summary
            : $this->plainTextContent($project->richtext_content);
        $short = trim((string) $project->short_description);

        if ($short === '') {
            return $summary;
        }

        if ($summary === '' || ! str_contains(mb_strtolower($summary), mb_strtolower($short))) {
            return trim($short.' '.$summary);
        }

        return $summary;
    }

    private function plainTextContent(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        $maxChars = (int) config('ai.project_search.max_content_chars', 2500);

        return mb_substr($text, 0, $maxChars);
    }
}
