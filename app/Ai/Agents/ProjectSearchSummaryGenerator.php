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
class ProjectSearchSummaryGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
You write compact search summaries for research project listings.

The user message is JSON with project metadata and full description text.

Write one English paragraph that:
- Captures the research topic, methods, materials, tools, and application domain.
- Includes synonyms and abbreviations a student might search for (e.g. "3D printing" and "additive manufacturing").
- Mentions experimental vs computational character when clear.
- Stays factual — only use information from the input.
- Fits within max_summary_chars from the input — end at a complete sentence, never mid-word.

Do not mention supervisors, section names, or thesis type unless essential to the topic.
TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->max(600)->required(),
        ];
    }
}
