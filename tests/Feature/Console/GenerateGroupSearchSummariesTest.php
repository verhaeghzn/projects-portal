<?php

use App\Ai\Agents\GroupSearchSummaryGenerator;
use App\Models\Group;
use App\Models\Project;
use App\Services\GroupSearchSummaryService;
use App\Services\ProjectSearchCatalogBuilder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    seedTestData();
});

test('generate group search summaries command stores llm summary on groups', function () {
    $project = createProject([
        'name' => 'Steel fatigue study',
        'short_description' => 'Metal experiments',
        'richtext_content' => '<p>Detailed fatigue testing on steel specimens in the lab.</p>',
        'is_published' => true,
    ]);

    $group = $project->fresh()->group;
    expect($group)->not->toBeNull();

    GroupSearchSummaryGenerator::fake([[
        'summary' => 'This group focuses on experimental mechanics and fatigue of metallic materials.',
    ]]);

    $exitCode = Artisan::call('groups:generate-search-summaries');

    expect($exitCode)->toBe(0);

    $group->refresh();

    expect($group->search_summary)->toBe('This group focuses on experimental mechanics and fatigue of metallic materials.');
    expect($group->search_summary_generated_at)->not->toBeNull();
});

test('generate group search summaries skips groups that are already up to date', function () {
    $project = createProject([
        'is_published' => true,
    ]);

    $group = $project->fresh()->group;
    expect($group)->not->toBeNull();

    Group::whereKey($group->id)->update([
        'search_summary' => 'Existing group summary.',
        'search_summary_generated_at' => now(),
    ]);

    Project::whereKey($project->id)->update([
        'updated_at' => now()->subMinute(),
    ]);

    GroupSearchSummaryGenerator::fake([[
        'summary' => 'Should not be used.',
    ]]);

    Artisan::call('groups:generate-search-summaries');

    expect($group->fresh()->search_summary)->toBe('Existing group summary.');
    GroupSearchSummaryGenerator::assertNeverPrompted();
});

test('group search summary is regenerated when a group project changes', function () {
    $project = createProject([
        'is_published' => true,
    ]);

    $group = $project->fresh()->group;
    expect($group)->not->toBeNull();

    Group::whereKey($group->id)->update([
        'search_summary' => 'Old group summary.',
        'search_summary_generated_at' => now()->subDay(),
    ]);

    $project->update(['short_description' => 'Updated description.']);

    expect((new GroupSearchSummaryService)->needsRegeneration($group->fresh()))->toBeTrue();
});

test('group summary service includes both available and past projects', function () {
    $available = createProject([
        'name' => 'Open steel project',
        'is_published' => true,
    ]);

    $group = $available->fresh()->group;
    expect($group)->not->toBeNull();

    $past = createProject([
        'name' => 'Completed polymer project',
        'student_name' => 'Jane Doe',
        'is_published' => true,
        'project_owner_id' => $available->project_owner_id,
    ]);
    $past->supervisorLinks()->delete();
    $past->supervisorLinks()->create([
        'supervisor_type' => $available->supervisorLinks->first()->supervisor_type,
        'supervisor_id' => $available->supervisorLinks->first()->supervisor_id,
        'order_rank' => 1,
    ]);

    $service = new GroupSearchSummaryService;
    $payload = $service->sourcePayload(
        $group->fresh()->load('section'),
        $service->projectsQuery($group)->with(['types', 'tags'])->get(),
    );

    expect($payload['project_counts']['total_published'])->toBe(2);
    expect($payload['project_counts']['available'])->toBe(1);
    expect($payload['project_counts']['past'])->toBe(1);
    expect(collect($payload['projects'])->pluck('name')->all())
        ->toContain('Open steel project', 'Completed polymer project');
});

test('catalog builder includes group descriptions in smart search payload', function () {
    $project = createProject([
        'name' => 'Steel fatigue study',
        'is_published' => true,
    ]);

    $group = $project->fresh()->group;
    expect($group)->not->toBeNull();

    Group::whereKey($group->id)->update([
        'search_summary' => 'Experimental mechanics group specializing in steel fatigue.',
    ]);

    $project->load(['types', 'tags', 'organization', 'supervisorLinks.supervisor.group.section']);
    $project->refresh();

    $catalog = (new ProjectSearchCatalogBuilder)->buildFromCandidates(
        collect([$project]),
        null,
        'master_thesis',
    );

    expect($catalog['groups'])->toHaveCount(1);
    expect($catalog['groups'][0]['id'])->toBe($group->id);
    expect($catalog['groups'][0]['description'])
        ->toBe('Experimental mechanics group specializing in steel fatigue.');
});
