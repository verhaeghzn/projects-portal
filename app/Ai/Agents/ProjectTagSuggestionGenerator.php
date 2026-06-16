<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4o-mini')]
#[Timeout(60)]
class ProjectTagSuggestionGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
You classify university research projects with predefined tags.

The user message is JSON with:
- "project": title, short_description, description (plain text), and types (thesis types).
- "available_tags": the ONLY tags you may assign. Each entry has id, name, and category (nature, focus, or group).

Rules:
- Return tag ids from available_tags only — never invent ids.
- Nature tags describe methodology: "Experimental" for lab work, testing, fabrication, or hands-on work; "Numerical" for simulation, modelling, or computational work. Pick one or both when clearly supported by the text.
- Focus tags describe topics, materials, or methods (e.g. metals, 3D printing, damage models). Pick every focus tag that clearly applies; synonyms count (e.g. additive manufacturing → 3D printing).
- Group tags are rare; only pick when the text explicitly matches a group tag name.
- Prefer recall for focus tags when the topic is clearly present, but do not guess from vague wording.
- When the project text is too thin to classify confidently, return an empty tag_ids array.
TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tag_ids' => $schema->array()
                ->items($schema->integer())
                ->required(),
        ];
    }
}
