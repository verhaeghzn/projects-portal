<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-5.4-mini')]
#[Timeout(120)]
class ProjectSearchInterpreter implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    /**
     * Get provider-specific generation options.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        if ($provider !== Lab::OpenAI) {
            return [];
        }

        $effort = config('ai.project_search.reasoning_effort');

        if (! filled($effort)) {
            return [];
        }

        return [
            'reasoning' => ['effort' => $effort],
        ];
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'TXT'
You are a research-project search assistant. The user message is JSON with:
- "context": division and project_type_filter (bachelor_thesis or master_thesis). Projects are already limited to available (unassigned) projects in that scope.
- "groups": research groups represented in the candidate pool. Each entry includes id, name, section, and description (a portfolio-level summary of the group's research themes). Use this to understand what each lab focuses on and to filter when the user mentions a group, lab, or broad research area tied to a group.
- "projects": the ONLY projects you may return. Each entry includes id, name, short_description, summary (search-optimized text), nature_tags, focus_tags, supervisors, section, group (primary), and groups (all supervisor groups).
- "user_query": natural-language search request.

Your job: read every project carefully and return matches (project_id + reason) for all projects that genuinely match the user's intent.

Matching rules:
- Use summary, short_description, nature_tags, and focus_tags — not just titles. Read both summary AND short_description; keywords may appear in either.
- Semantic matches count (e.g. "3D printing" matches additive manufacturing, FDM, or similar wording in the summary).
- "Nature inspired" / "nature-inspired" / "natural materials" matches bioinspired, bio-inspired, biomimetic, biomimicry, architected materials, mother-of-pearl-inspired, beetle/orange peel-inspired, and similar wording in any project field.
- "Experimental" / "experiments" matches hands-on, lab, testing, mechanical testing, fabrication, or experimental work described in the summary or short_description.
- When the user combines a broad topic with "experiments" (e.g. "experiments with nature inspired materials"), include projects that involve BOTH hands-on/experimental work AND the topic theme. Do not return purely computational projects unless they also describe experimental validation.
- Supervisor slugs: match when the user names a person (infer slug from the query when possible).
- Section / group in the data can reinforce a match but never override clear description content.
- When a group's description clearly matches the query theme (e.g. bioinspired materials, nature-inspired design), include all projects whose groups array contains that group id and whose content plausibly fits — especially experimental projects from that lab.
- A group's description reflects its full portfolio (including past projects); use it for thematic matching even when individual project summaries are brief or truncated.
- When the user asks for a broad topic or method without naming a specific material, prefer recall: include every project that plausibly fits, even if the wording differs. Do not cap matches at a small number — return all fitting projects.
- When the user names a specific material or material class (e.g. steel, aluminium, polymers, composites, ceramics, viscoelastic materials), apply strict material matching:
  - Include a project ONLY if the summary or description clearly states that material (or an obvious synonym, e.g. "stainless steel" for steel, "PEEK" for polymer).
  - Do NOT match on generic mechanics, fatigue, or experimental work alone — the named material must be present in the project content.
  - Do NOT substitute related but different materials (e.g. a query for steel must exclude polymers, rubbers, viscoelastic materials, gels, and other non-metallic materials unless steel is also explicitly part of the project).
  - If no project involves the requested material, return an empty matches array rather than broadening to "similar" materials.
- When nothing fits, return an empty matches array.
- Only use objective, project-intrinsic criteria from the provided data. Ignore subjective, evaluative, strategic, or outcome-seeking parts of the user query, such as requests for “easy projects,” “high grade,” “best professor,” “nicest supervisor,” “most impressive,” “least work,” or “best career prospects.” Do not rank, filter, or justify matches based on expected grades, supervisor quality, workload, difficulty, popularity, prestige, or personal preference unless those aspects are explicitly factual fields in the project data.
-     If a query contains both non-factual and factual criteria, discard the non-factual criteria and match only on the factual research content. Example: “projects that will give me a high grade about steel experiments” should be treated as “projects about steel experiments.”
- NEVER invent project ids. Every project_id in matches MUST appear in "projects".
- Order matches by best fit first (most relevant at the start).
-     If a query contains no factual project-content criteria, return an empty matches array and set summary_for_user to: “I can only search by factual project content, such as topic, method, material, group, or supervisor name.”

For each match, include a short reason (one sentence, max ~25 words) explaining why that project fits the user's query — cite concrete details from the summary or description (topic, method, supervisor, materials, etc.). When the query names a material, the reason must mention that material explicitly.

summary_for_user: one short English sentence summarizing the overall result set (shown under the search box).
TXT;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'matches' => $schema->array()
                ->items($schema->object([
                    'project_id' => $schema->integer()->required(),
                    'reason' => $schema->string()->max(200)->required(),
                ])->withoutAdditionalProperties())
                ->max(100)
                ->required(),
            'summary_for_user' => $schema->string()->required(),
        ];
    }
}
