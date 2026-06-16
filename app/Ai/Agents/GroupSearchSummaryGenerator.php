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
class GroupSearchSummaryGenerator implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TXT'
You write compact search summaries for university research groups.

The user message is JSON with group metadata and a list of thesis projects supervised by members of that group (both current openings and completed past projects).

Write two or three English sentences that:
- Describe the group's core research themes, methods, materials, and application domains.
- Include synonyms and abbreviations a student might search for.
- Capture recurring topics across the project portfolio without listing individual project titles.
- Stay factual — only use information from the input.
- Fit within max_summary_chars from the input — end at a complete sentence, never mid-word.

Do not mention individual supervisor names unless essential to the research focus.
TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'summary' => $schema->string()->max(600)->required(),
        ];
    }
}
