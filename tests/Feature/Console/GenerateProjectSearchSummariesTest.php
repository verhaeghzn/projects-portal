<?php

use App\Ai\Agents\ProjectSearchSummaryGenerator;
use App\Models\Project;
use App\Services\ProjectSearchCatalogBuilder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    seedTestData();
});

test('generate search summaries command stores llm summary on projects', function () {
    createSupervisor();

    $project = createProject([
        'name' => 'Steel fatigue study',
        'short_description' => 'Metal experiments',
        'richtext_content' => '<p>Detailed fatigue testing on steel specimens in the lab.</p>',
        'is_published' => true,
    ]);

    ProjectSearchSummaryGenerator::fake([[
        'summary' => 'Experimental fatigue testing on steel specimens for structural applications.',
    ]]);

    $exitCode = Artisan::call('projects:generate-search-summaries');

    expect($exitCode)->toBe(0);

    $project->refresh();

    expect($project->search_summary)->toBe('Experimental fatigue testing on steel specimens for structural applications.');
    expect($project->search_summary_generated_at)->not->toBeNull();
});

test('generate search summaries skips projects that are already up to date', function () {
    createSupervisor();

    $project = createProject([
        'is_published' => true,
    ]);

    Project::whereKey($project->id)->update([
        'search_summary' => 'Existing summary.',
        'search_summary_generated_at' => now(),
        'updated_at' => now()->subMinute(),
    ]);

    ProjectSearchSummaryGenerator::fake([[
        'summary' => 'Should not be used.',
    ]]);

    Artisan::call('projects:generate-search-summaries');

    expect($project->fresh()->search_summary)->toBe('Existing summary.');
    ProjectSearchSummaryGenerator::assertNeverPrompted();
});

test('project content changes invalidate the cached search summary', function () {
    createSupervisor();

    $project = createProject([
        'is_published' => true,
    ]);

    Project::whereKey($project->id)->update([
        'search_summary' => 'Old summary.',
        'search_summary_generated_at' => now()->subDay(),
    ]);

    $project->update(['short_description' => 'Updated description.']);

    expect($project->fresh()->search_summary)->toBeNull();
    expect($project->fresh()->search_summary_generated_at)->toBeNull();
});

test('catalog builder uses pre-generated search summary in smart search payload', function () {
    createSupervisor();

    $project = createProject([
        'name' => 'Steel fatigue study',
        'short_description' => 'Metal experiments',
        'richtext_content' => '<p>'.str_repeat('Original long content. ', 200).'</p>',
        'is_published' => true,
    ]);

    Project::whereKey($project->id)->update([
        'search_summary' => 'Compact steel fatigue summary for search.',
    ]);

    $project->load(['types', 'tags', 'organization', 'supervisorLinks.supervisor.group.section']);
    $project->refresh();

    $catalog = (new ProjectSearchCatalogBuilder)->buildFromCandidates(
        collect([$project]),
        null,
        'master_thesis',
    );

    expect($catalog['projects'][0]['summary'])->toContain('Compact steel fatigue summary for search.');
    expect($catalog['projects'][0]['summary'])->toContain('Metal experiments');
    expect($catalog['projects'][0]['summary'])->not->toContain('Original long content');
    expect($catalog['projects'][0])->toHaveKey('groups');
    expect($catalog['projects'][0])->toHaveKey('nature_tags');
    expect($catalog['projects'][0])->toHaveKey('focus_tags');
});
