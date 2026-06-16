<?php

use App\Ai\Agents\ProjectTagSuggestionGenerator;
use App\Models\Tag;
use App\Models\TagCategory;
use App\Services\ProjectTagSuggestionService;

beforeEach(function () {
    seedTestData();
});

test('project tag suggestion service returns llm suggested tag ids', function () {
    $experimental = Tag::factory()->create([
        'name' => 'Experimental',
        'category' => TagCategory::Nature,
    ]);
    $steel = Tag::factory()->create([
        'name' => 'Steel',
        'category' => TagCategory::Focus,
    ]);
    Tag::factory()->create([
        'name' => 'Numerical',
        'category' => TagCategory::Nature,
    ]);

    ProjectTagSuggestionGenerator::fake([[
        'tag_ids' => [$experimental->id, $steel->id, 99999],
    ]]);

    $tagIds = (new ProjectTagSuggestionService)->suggest([
        'name' => 'Fatigue of steel specimens',
        'short_description' => 'Mechanical testing in the lab.',
        'richtext_content' => '<p>Students will perform experimental fatigue tests on steel samples.</p>',
        'types' => [],
    ]);

    expect($tagIds)->toBe([$experimental->id, $steel->id]);
    ProjectTagSuggestionGenerator::assertPrompted(fn ($prompt) => str_contains($prompt->prompt, 'Fatigue of steel specimens'));
});

test('project tag suggestion service returns empty array when project text is too thin', function () {
    Tag::factory()->create([
        'name' => 'Experimental',
        'category' => TagCategory::Nature,
    ]);

    ProjectTagSuggestionGenerator::fake([[
        'tag_ids' => [1],
    ]]);

    $tagIds = (new ProjectTagSuggestionService)->suggest([
        'name' => 'Untitled',
        'short_description' => '',
        'richtext_content' => '',
    ]);

    expect($tagIds)->toBe([]);
    ProjectTagSuggestionGenerator::assertNeverPrompted();
});

test('project tag suggestion service returns null without openai key', function () {
    config(['ai.providers.openai.key' => null]);

    Tag::factory()->create([
        'name' => 'Experimental',
        'category' => TagCategory::Nature,
    ]);

    $tagIds = (new ProjectTagSuggestionService)->suggest([
        'name' => 'Steel project',
        'short_description' => 'Lab work on steel.',
        'richtext_content' => 'Detailed description.',
    ]);

    expect($tagIds)->toBeNull();
});
