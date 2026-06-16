<?php

namespace App\Services;

use App\Ai\Agents\ProjectSearchInterpreter;
use App\Data\CatalogLookup;
use App\Data\SmartSearchCriteria;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

class ProjectSmartSearchService
{
    private const int MAX_KEYWORDS = 12;

    private const int MAX_KEYWORD_LENGTH = 40;

    public function __construct(
        private ProjectSearchCatalogBuilder $catalogBuilder,
    ) {}

    /**
     * @param  Collection<int, Project>  $candidates
     * @param  array{name: string, slug: string, section_slugs: list<string>}|null  $selectedDivision
     * @return array{
     *     criteria: ?SmartSearchCriteria,
     *     summary: ?string,
     *     error: ?string,
     *     debug: ?array{
     *         model: string,
     *         instructions: string,
     *         user_message: array<string, mixed>,
     *         structured_response: ?array<string, mixed>,
     *         applied_criteria: array<string, mixed>
     *     }
     * }
     */
    public function interpret(
        string $userQuery,
        ?array $selectedDivision,
        ?string $thesisType,
        Collection $candidates,
        array $filters = [],
    ): array {
        $trimmed = trim($userQuery);
        if ($trimmed === '') {
            return ['criteria' => null, 'summary' => null, 'error' => null, 'debug' => null];
        }

        if (! $this->shouldCacheInterpretation()) {
            return $this->interpretUncached($trimmed, $selectedDivision, $thesisType, $candidates);
        }

        $cacheKey = $this->interpretCacheKey($trimmed, $selectedDivision, $thesisType, $candidates, $filters);
        $ttl = (int) config('ai.project_search.cache_ttl', 21600);

        $cached = Cache::remember(
            $cacheKey,
            $ttl,
            fn () => $this->serializeInterpretResult(
                $this->interpretUncached($trimmed, $selectedDivision, $thesisType, $candidates)
            ),
        );

        return $this->deserializeInterpretResult($cached);
    }

    /**
     * @param  Collection<int, Project>  $candidates
     * @param  array{name: string, slug: string, section_slugs: list<string>}|null  $selectedDivision
     * @param  array<string, mixed>  $filters
     * @return array{
     *     criteria: ?SmartSearchCriteria,
     *     summary: ?string,
     *     error: ?string,
     *     debug: ?array{
     *         model: string,
     *         instructions: string,
     *         user_message: array<string, mixed>,
     *         structured_response: ?array<string, mixed>,
     *         applied_criteria: array<string, mixed>
     *     }
     * }
     */
    private function interpretUncached(
        string $trimmed,
        ?array $selectedDivision,
        ?string $thesisType,
        Collection $candidates,
    ): array {
        $catalog = $this->catalogBuilder->buildFromCandidates($candidates, $selectedDivision, $thesisType);
        $lookup = $this->lookupFromCatalog($catalog);
        $agent = new ProjectSearchInterpreter;
        $instructions = (string) $agent->instructions();
        $model = config('ai.project_search.model', 'gpt-5.4-mini');
        $userMessage = ['catalog' => $catalog, 'user_query' => $trimmed];

        if ($candidates->isEmpty()) {
            return [
                'criteria' => new SmartSearchCriteria(
                    summaryForUser: 'No available projects in this scope to search.',
                ),
                'summary' => 'No available projects in this scope to search.',
                'error' => null,
                'debug' => $this->debugContext($instructions, $userMessage, $model, null, null),
            ];
        }

        $openAiKey = config('ai.providers.openai.key');
        if (! filled($openAiKey) && ! ProjectSearchInterpreter::isFaked()) {
            $criteria = $this->criteriaFromFallbackKeywords($trimmed, $candidates);

            return [
                'criteria' => $criteria,
                'summary' => $criteria->summaryForUser,
                'error' => 'Smart search AI is not configured (missing OPENAI_API_KEY). Showing keyword-only results.',
                'debug' => $this->debugContext($instructions, $userMessage, $model, null, $criteria),
            ];
        }

        try {
            $payload = json_encode(
                $userMessage,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
            );
        } catch (Throwable $e) {
            return [
                'criteria' => null,
                'summary' => null,
                'error' => $this->userFacingError('Could not prepare search request.', $e),
                'debug' => $this->debugContext($instructions, $userMessage, $model, null, null),
            ];
        }

        $structuredResponse = null;

        try {
            /** @var StructuredAgentResponse $response */
            $response = $agent->prompt(
                $payload,
                model: $model,
            );
            $structuredResponse = $response->toArray();
        } catch (Throwable $e) {
            Log::warning('Project smart search AI failed', ['exception' => $e]);

            $criteria = $this->criteriaFromFallbackKeywords($trimmed, $candidates);

            return [
                'criteria' => $criteria,
                'summary' => $criteria->summaryForUser,
                'error' => $this->userFacingError(
                    'Smart search is temporarily unavailable. Showing keyword-only results.',
                    $e,
                ),
                'debug' => $this->debugContext($instructions, $userMessage, $model, null, $criteria),
            ];
        }

        $criteria = $this->validateStructuredResponse($structuredResponse, $lookup);
        $criteria = $this->supplementThematicMatches($trimmed, $candidates, $catalog, $criteria);

        if ($criteria->isEmpty()) {
            $criteria = $this->criteriaFromFallbackKeywords($trimmed, $candidates);
        }

        return [
            'criteria' => $criteria,
            'summary' => $criteria->summaryForUser,
            'error' => null,
            'debug' => $this->debugContext($instructions, $userMessage, $model, $structuredResponse, $criteria),
        ];
    }

    /**
     * @param  Collection<int, Project>  $candidates
     * @param  array{name: string, slug: string, section_slugs: list<string>}|null  $selectedDivision
     * @param  array<string, mixed>  $filters
     */
    private function interpretCacheKey(
        string $query,
        ?array $selectedDivision,
        ?string $thesisType,
        Collection $candidates,
        array $filters,
    ): string {
        $normalizedFilters = [];
        foreach (['type', 'section', 'supervisor', 'group'] as $filter) {
            $value = $filters[$filter] ?? null;
            $normalizedFilters[$filter] = ($value === null || $value === '') ? '_' : (string) $value;
        }

        $divisionKey = $selectedDivision !== null
            ? ($selectedDivision['slug'] ?? '_').':'.implode(',', $selectedDivision['section_slugs'] ?? [])
            : '_';

        $candidateFingerprint = hash(
            'xxh128',
            $candidates->pluck('id')->sort()->values()->implode(','),
        );

        $payload = json_encode([
            'v' => 1,
            'q' => mb_strtolower($query),
            'thesis_type' => $thesisType ?? 'master_thesis',
            'division' => $divisionKey,
            'filters' => $normalizedFilters,
            'candidates' => $candidateFingerprint,
            'model' => config('ai.project_search.model', 'gpt-5.4-mini'),
        ], JSON_THROW_ON_ERROR);

        return 'project-smart-search:'.hash('xxh128', $payload);
    }

    private function shouldCacheInterpretation(): bool
    {
        if (! config('ai.project_search.cache_enabled', true)) {
            return false;
        }

        if (config('app.debug')) {
            return false;
        }

        if (ProjectSearchInterpreter::isFaked()) {
            return false;
        }

        return true;
    }

    /**
     * @param  array{
     *     criteria: ?SmartSearchCriteria,
     *     summary: ?string,
     *     error: ?string,
     *     debug: ?array<string, mixed>
     * }  $result
     * @return array{criteria: ?array<string, mixed>, summary: ?string, error: ?string}
     */
    private function serializeInterpretResult(array $result): array
    {
        $criteria = $result['criteria'];

        return [
            'criteria' => $criteria !== null ? $this->criteriaToArray($criteria) : null,
            'summary' => $result['summary'],
            'error' => $result['error'],
        ];
    }

    /**
     * @param  array{criteria: ?array<string, mixed>, summary: ?string, error: ?string}  $cached
     * @return array{
     *     criteria: ?SmartSearchCriteria,
     *     summary: ?string,
     *     error: ?string,
     *     debug: null
     * }
     */
    private function deserializeInterpretResult(array $cached): array
    {
        $criteriaData = $cached['criteria'] ?? null;

        return [
            'criteria' => is_array($criteriaData)
                ? new SmartSearchCriteria(
                    projectIds: array_values(array_map('intval', $criteriaData['project_ids'] ?? [])),
                    summaryForUser: $criteriaData['summary_for_user'] ?? null,
                    projectReasons: collect($criteriaData['project_reasons'] ?? [])
                        ->mapWithKeys(fn ($reason, $id) => [(int) $id => (string) $reason])
                        ->all(),
                )
                : null,
            'summary' => $cached['summary'] ?? null,
            'error' => $cached['error'] ?? null,
            'debug' => null,
        ];
    }

    /**
     * @param  Collection<int, Project>  $candidates
     * @param  array<string, mixed>  $catalog
     */
    private function supplementThematicMatches(
        string $query,
        Collection $candidates,
        array $catalog,
        SmartSearchCriteria $criteria,
    ): SmartSearchCriteria {
        $queryLower = mb_strtolower($query);
        $wantsNature = (bool) preg_match('/nature|bio-?inspired|biomimetic|biomimicry/u', $queryLower);
        if (! $wantsNature) {
            return $criteria;
        }

        $wantsExperimental = (bool) preg_match('/experiment/u', $queryLower);
        $naturePattern = '/bio-?inspired|nature-?inspired|biomimetic|biomimicry|mother-of-pearl|beetle|orange peel|architected material/i';
        $experimentalPattern = '/experimental|mechanical test|fabricat|manufactur|characteriz|\blab\b|additive manufacturing|3d print|SEM\b|microscopy/i';

        $thematicGroupIds = collect($catalog['groups'] ?? [])
            ->filter(fn (array $group) => preg_match($naturePattern, (string) ($group['description'] ?? '')))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $catalogProjects = collect($catalog['projects'] ?? [])->keyBy('id');
        $supplemental = [];

        foreach ($candidates as $project) {
            $entry = $catalogProjects->get($project->id);
            if (! is_array($entry)) {
                continue;
            }

            $text = implode(' ', array_filter([
                $entry['name'] ?? '',
                $entry['short_description'] ?? '',
                $entry['summary'] ?? '',
            ]));

            $inThematicGroup = collect($entry['groups'] ?? [])
                ->contains(fn (array $group) => in_array((int) ($group['id'] ?? 0), $thematicGroupIds, true));

            $hasNature = (bool) preg_match($naturePattern, $text);
            $hasExperimental = (bool) preg_match($experimentalPattern, $text);

            $fits = $wantsExperimental
                ? (($hasNature || $inThematicGroup) && $hasExperimental)
                : ($hasNature || $inThematicGroup);

            if ($fits) {
                $supplemental[] = $project->id;
            }
        }

        if ($supplemental === []) {
            return $criteria;
        }

        $mergedIds = $criteria->projectIds;
        $reasons = $criteria->projectReasons;
        $fallbackReason = $wantsExperimental
            ? 'Matches nature-inspired materials with experimental or hands-on work.'
            : 'Matches nature-inspired or bioinspired materials research.';

        foreach ($supplemental as $id) {
            if (! in_array($id, $mergedIds, true)) {
                $mergedIds[] = $id;
                if (! isset($reasons[$id])) {
                    $reasons[$id] = $fallbackReason;
                }
            }
        }

        return new SmartSearchCriteria(
            projectIds: $mergedIds,
            summaryForUser: $criteria->summaryForUser,
            projectReasons: $reasons,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateStructuredResponse(array $data, CatalogLookup $lookup): SmartSearchCriteria
    {
        $allowedIds = array_flip($lookup->projectIds);
        $projectIds = [];
        $projectReasons = [];

        foreach ($data['matches'] ?? [] as $match) {
            if (! is_array($match)) {
                continue;
            }

            $id = $match['project_id'] ?? null;
            if (! is_numeric($id)) {
                continue;
            }

            $intId = (int) $id;
            if (! isset($allowedIds[$intId]) || in_array($intId, $projectIds, true)) {
                continue;
            }

            $projectIds[] = $intId;

            $reason = $match['reason'] ?? null;
            if (is_string($reason)) {
                $trimmedReason = trim($reason);
                if ($trimmedReason !== '') {
                    $projectReasons[$intId] = mb_substr($trimmedReason, 0, 200);
                }
            }
        }

        // Backward compatibility for legacy project_ids-only responses.
        if ($projectIds === []) {
            foreach ($data['project_ids'] ?? [] as $id) {
                if (! is_numeric($id)) {
                    continue;
                }
                $intId = (int) $id;
                if (isset($allowedIds[$intId])) {
                    $projectIds[] = $intId;
                }
            }

            $projectIds = array_values(array_unique($projectIds));
        }

        $summary = isset($data['summary_for_user']) && is_string($data['summary_for_user'])
            ? mb_substr(trim($data['summary_for_user']), 0, 500)
            : null;

        return new SmartSearchCriteria(
            projectIds: $projectIds,
            summaryForUser: $summary,
            projectReasons: $projectReasons,
        );
    }

    public function applyToQuery(Builder $query, SmartSearchCriteria $criteria): void
    {
        if ($criteria->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('projects.id', $criteria->projectIds);
    }

    /**
     * @param  Collection<int, Project>  $candidates
     */
    public function orderProjects(Collection $projects, SmartSearchCriteria $criteria): Collection
    {
        if ($criteria->projectIds === []) {
            return $projects;
        }

        $order = array_flip($criteria->projectIds);

        return $projects
            ->sortBy(fn (Project $project) => $order[$project->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @param  Collection<int, Project>  $candidates
     */
    private function criteriaFromFallbackKeywords(string $trimmedQuery, Collection $candidates): SmartSearchCriteria
    {
        $keywords = [];
        foreach (preg_split('/\s+/u', $trimmedQuery, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $normalized = $this->normalizeKeywordToken($part);
            if ($normalized !== null) {
                $keywords[] = $normalized;
            }
            if (count($keywords) >= self::MAX_KEYWORDS) {
                break;
            }
        }
        $keywords = array_values(array_unique($keywords));

        if ($keywords === []) {
            return new SmartSearchCriteria(
                summaryForUser: 'No searchable terms found in your query.',
            );
        }

        $matchingProjects = $candidates
            ->filter(function (Project $project) use ($keywords) {
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $project->name,
                    $project->short_description,
                    strip_tags((string) $project->richtext_content),
                ])));

                foreach ($keywords as $keyword) {
                    if (! str_contains($haystack, mb_strtolower($keyword))) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $matchingIds = $matchingProjects
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $fallbackReason = 'Matched your search keywords in the project description.';
        $projectReasons = $matchingProjects
            ->mapWithKeys(fn (Project $project) => [$project->id => $fallbackReason])
            ->all();

        return new SmartSearchCriteria(
            projectIds: $matchingIds,
            summaryForUser: $matchingIds === []
                ? 'Keyword search: no projects matched '.implode(', ', $keywords).'.'
                : 'Keyword search: '.implode(', ', $keywords),
            projectReasons: $projectReasons,
        );
    }

    private function normalizeKeywordToken(string $raw): ?string
    {
        $t = preg_replace('/[^a-zA-Z0-9\-]/u', '', $raw) ?? '';
        $len = strlen($t);
        if ($len < 2 || $len > self::MAX_KEYWORD_LENGTH) {
            return null;
        }

        return $t;
    }

    private function userFacingError(string $message, ?Throwable $cause = null): string
    {
        if (! config('app.debug') || $cause === null) {
            return $message;
        }

        $details = trim($cause->getMessage());

        return $details === ''
            ? $message.' ('.class_basename($cause).')'
            : $message.' ('.class_basename($cause).': '.$details.')';
    }

    /**
     * @param  array<string, mixed>  $userMessage
     * @param  array<string, mixed>|null  $structuredResponse
     * @return array{
     *     model: string,
     *     instructions: string,
     *     user_message: array<string, mixed>,
     *     structured_response: ?array<string, mixed>,
     *     applied_criteria: array<string, mixed>
     * }|null
     */
    private function debugContext(
        string $instructions,
        array $userMessage,
        string $model,
        ?array $structuredResponse,
        ?SmartSearchCriteria $appliedCriteria,
    ): ?array {
        if (! config('app.debug')) {
            return null;
        }

        return [
            'model' => $model,
            'instructions' => $instructions,
            'user_message' => $userMessage,
            'structured_response' => $structuredResponse,
            'applied_criteria' => $appliedCriteria !== null
                ? $this->criteriaToArray($appliedCriteria)
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function criteriaToArray(SmartSearchCriteria $criteria): array
    {
        return [
            'project_ids' => $criteria->projectIds,
            'project_reasons' => $criteria->projectReasons,
            'summary_for_user' => $criteria->summaryForUser,
        ];
    }

    /**
     * @param  array<string, mixed>  $catalog
     */
    private function lookupFromCatalog(array $catalog): CatalogLookup
    {
        $projectIds = array_values(array_filter(array_map(
            fn ($row) => is_array($row) && isset($row['id']) && is_numeric($row['id']) ? (int) $row['id'] : null,
            $catalog['projects'] ?? []
        )));

        return new CatalogLookup(projectIds: $projectIds);
    }
}
