<?php

namespace App\Services;

use App\Ai\Agents\ProjectTagSuggestionGenerator;
use App\Models\ProjectType;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ProjectTagSuggestionService
{
    /**
     * @param  array{name?: string|null, short_description?: string|null, richtext_content?: string|null, types?: array<int|string>|null}  $projectData
     * @return array<int>|null
     */
    public function suggest(array $projectData): ?array
    {
        $openAiKey = config('ai.providers.openai.key');
        if (! filled($openAiKey) && ! ProjectTagSuggestionGenerator::isFaked()) {
            return null;
        }

        $name = trim((string) ($projectData['name'] ?? ''));
        $shortDescription = trim((string) ($projectData['short_description'] ?? ''));
        $description = $this->plainTextContent($projectData['richtext_content'] ?? null);

        if ($name === '' || ($shortDescription === '' && $description === '')) {
            return [];
        }

        $tags = Tag::query()->orderBy('category')->orderBy('name')->get();
        if ($tags->isEmpty()) {
            return [];
        }

        $typeNames = $this->resolveTypeNames($projectData['types'] ?? []);

        $payload = json_encode([
            'project' => [
                'name' => $name,
                'short_description' => $shortDescription,
                'description' => $description,
                'types' => $typeNames,
            ],
            'available_tags' => $tags->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'category' => $tag->category->value,
            ])->values()->all(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $model = config('ai.project_search.tag_suggestion_model', 'gpt-4o-mini');
        $validTagIds = $tags->pluck('id')->all();

        try {
            /** @var StructuredAgentResponse $response */
            $response = (new ProjectTagSuggestionGenerator)->prompt(
                $payload,
                model: $model,
            );

            $tagIds = collect($response->toArray()['tag_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => in_array($id, $validTagIds, true))
                ->unique()
                ->values()
                ->all();

            return $tagIds;
        } catch (Throwable $e) {
            Log::warning('Project tag suggestion failed', [
                'project_name' => $name,
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    /**
     * @param  array<int|string>|Collection<int|string>  $typeIds
     * @return array<int, string>
     */
    private function resolveTypeNames(array|Collection $typeIds): array
    {
        $typeIds = collect($typeIds)->filter()->values();

        if ($typeIds->isEmpty()) {
            return [];
        }

        return ProjectType::query()
            ->whereIn('id', $typeIds)
            ->pluck('name')
            ->values()
            ->all();
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
