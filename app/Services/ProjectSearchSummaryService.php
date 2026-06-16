<?php

namespace App\Services;

use App\Ai\Agents\ProjectSearchSummaryGenerator;
use App\Models\Project;
use App\Models\TagCategory;
use App\Models\User;
use App\Support\SearchSummaryText;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ProjectSearchSummaryService
{
    public function needsRegeneration(Project $project, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        if (! filled($project->search_summary)) {
            return true;
        }

        if ($project->search_summary_generated_at === null) {
            return true;
        }

        return $project->updated_at !== null
            && $project->search_summary_generated_at->lt($project->updated_at);
    }

    public function generateFor(Project $project): ?string
    {
        $openAiKey = config('ai.providers.openai.key');
        if (! filled($openAiKey) && ! ProjectSearchSummaryGenerator::isFaked()) {
            return null;
        }

        $project->loadMissing([
            'types',
            'tags',
            'organization',
            'supervisorLinks.supervisor.group.section',
        ]);

        $payload = json_encode(
            $this->sourcePayload($project),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
        );

        $model = config('ai.project_search.summary_model', config('ai.project_search.model', 'gpt-4o-mini'));
        $maxChars = (int) config('ai.project_search.summary_max_chars', 400);

        try {
            /** @var StructuredAgentResponse $response */
            $response = (new ProjectSearchSummaryGenerator)->prompt(
                $payload,
                model: $model,
            );

            $summary = trim((string) ($response->toArray()['summary'] ?? ''));
            if ($summary === '') {
                return null;
            }

            $summary = SearchSummaryText::limit($summary, $maxChars);

            $project->forceFill([
                'search_summary' => $summary,
                'search_summary_generated_at' => now(),
            ])->saveQuietly();

            return $summary;
        } catch (Throwable $e) {
            Log::warning('Project search summary generation failed', [
                'project_id' => $project->id,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function sourcePayload(Project $project): array
    {
        $firstSupervisor = $project->supervisorLinks
            ->first(fn ($link) => $link->supervisor_type === User::class)
            ?->supervisor;

        return [
            'name' => $project->name,
            'short_description' => $project->short_description,
            'description' => $this->plainTextContent($project->richtext_content),
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
            'section' => $firstSupervisor?->group?->section?->name,
            'group' => $firstSupervisor?->group?->name,
            'organization' => $project->organization?->name,
            'max_summary_chars' => (int) config('ai.project_search.summary_max_chars', 400),
        ];
    }

    private function plainTextContent(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        return mb_substr($text, 0, 8000);
    }
}
