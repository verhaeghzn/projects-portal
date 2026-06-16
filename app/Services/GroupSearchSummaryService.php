<?php

namespace App\Services;

use App\Ai\Agents\GroupSearchSummaryGenerator;
use App\Models\Group;
use App\Models\Project;
use App\Models\TagCategory;
use App\Models\User;
use App\Support\SearchSummaryText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class GroupSearchSummaryService
{
    public function needsRegeneration(Group $group, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        if (! filled($group->search_summary)) {
            return true;
        }

        if ($group->search_summary_generated_at === null) {
            return true;
        }

        $latestProjectUpdate = $this->projectsQuery($group)->max('updated_at');

        if ($latestProjectUpdate === null) {
            return false;
        }

        return $group->search_summary_generated_at->lt($latestProjectUpdate);
    }

    public function generateFor(Group $group): ?string
    {
        $openAiKey = config('ai.providers.openai.key');
        if (! filled($openAiKey) && ! GroupSearchSummaryGenerator::isFaked()) {
            return null;
        }

        $group->loadMissing('section');

        $projects = $this->projectsQuery($group)
            ->with(['types', 'tags'])
            ->orderByDesc('updated_at')
            ->limit((int) config('ai.project_search.group_summary_max_projects', 40))
            ->get();

        if ($projects->isEmpty()) {
            return null;
        }

        $payload = json_encode(
            $this->sourcePayload($group, $projects),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        $model = config('ai.project_search.group_summary_model', config('ai.project_search.summary_model', 'gpt-4o-mini'));
        $maxChars = (int) config('ai.project_search.group_summary_max_chars', 500);

        try {
            /** @var StructuredAgentResponse $response */
            $response = (new GroupSearchSummaryGenerator)->prompt(
                $payload,
                model: $model,
            );

            $summary = trim((string) ($response->toArray()['summary'] ?? ''));
            if ($summary === '') {
                return null;
            }

            $summary = SearchSummaryText::limit($summary, $maxChars);

            $group->forceFill([
                'search_summary' => $summary,
                'search_summary_generated_at' => now(),
            ])->saveQuietly();

            return $summary;
        } catch (Throwable $e) {
            Log::warning('Group search summary generation failed', [
                'group_id' => $group->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    public function sourcePayload(Group $group, $projects): array
    {
        $totalCount = $this->projectsQuery($group)->count();
        $availableCount = $this->projectsQuery($group)->available()->count();

        return [
            'group' => $group->name,
            'section' => $group->section?->name,
            'project_counts' => [
                'total_published' => $totalCount,
                'available' => $availableCount,
                'past' => $totalCount - $availableCount,
                'included_in_prompt' => $projects->count(),
            ],
            'projects' => $projects->map(fn (Project $project) => [
                'name' => $project->name,
                'short_description' => $project->short_description,
                'summary' => filled($project->search_summary)
                    ? $project->search_summary
                    : $this->plainTextContent($project->richtext_content),
                'types' => $project->types->pluck('name')->values()->all(),
                'nature_tags' => $project->tags
                    ->where('category', TagCategory::Nature->value)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'focus_tags' => $project->tags
                    ->where('category', TagCategory::Focus->value)
                    ->pluck('name')
                    ->values()
                    ->all(),
                'status' => $project->is_taken ? 'past' : 'available',
            ])->values()->all(),
            'max_summary_chars' => (int) config('ai.project_search.group_summary_max_chars', 500),
        ];
    }

    public function projectsQuery(Group $group): Builder
    {
        return Project::query()
            ->where('is_published', true)
            ->whereHas('supervisorLinks', function ($query) use ($group) {
                $query->where('supervisor_type', User::class)
                    ->whereHasMorph('supervisor', [User::class], function ($supervisorQuery) use ($group) {
                        $supervisorQuery->where('group_id', $group->id);
                    });
            });
    }

    private function plainTextContent(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_substr($text, 0, 1200);
    }
}
