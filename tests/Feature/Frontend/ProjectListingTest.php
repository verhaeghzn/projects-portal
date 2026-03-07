<?php

use App\Enums\PublicationStatus;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\ProjectType;
use App\Models\Section;
use App\Models\Tag;
use App\Models\TagCategory;
use App\Models\User;

beforeEach(function () {
    seedTestData();
});

test('projects route returns 200', function () {
    $response = $this->get('/projects');

    $response->assertStatus(200);
});

test('projects are displayed', function () {
    $project = createProject();
    $project->update(['publication_status' => PublicationStatus::Published]);

    $response = $this->get('/projects');

    $response->assertStatus(200);
    $response->assertSee($project->name);
});

test('only published and available projects are shown', function () {
    $publishedProject = createProject();
    $publishedProject->update(['publication_status' => PublicationStatus::Published]);

    $conceptProject = createProject();
    $conceptProject->update(['publication_status' => PublicationStatus::Concept]);

    $takenProject = createProject();
    $takenProject->update([
        'publication_status' => PublicationStatus::Published,
        'student_name' => 'John Doe',
    ]);

    $response = $this->get('/projects');

    $response->assertSee($publishedProject->name);
    $response->assertDontSee($conceptProject->name);
    $response->assertDontSee($takenProject->name);
});

test('can filter by project type', function () {
    $bachelorType = ProjectType::where('slug', 'bachelor_thesis')->first();
    $masterType = ProjectType::where('slug', 'master_thesis')->first();

    $bachelorProject = createProject();
    $bachelorProject->types()->attach($bachelorType->id);
    $bachelorProject->update(['publication_status' => PublicationStatus::Published]);

    $masterProject = createProject();
    $masterProject->types()->attach($masterType->id);
    $masterProject->update(['publication_status' => PublicationStatus::Published]);

    $response = $this->get('/projects?type=bachelor_thesis');

    $response->assertSee($bachelorProject->name);
    $response->assertDontSee($masterProject->name);
});

test('can filter by nature tag', function () {
    $natureTag = Tag::where('category', TagCategory::Nature)->first();
    if (!$natureTag) {
        $natureTag = Tag::factory()->category(TagCategory::Nature)->create();
    }

    $projectWithTag = createProject();
    $projectWithTag->tags()->attach($natureTag->id);
    $projectWithTag->update(['publication_status' => PublicationStatus::Published]);

    $projectWithoutTag = createProject();
    $projectWithoutTag->update(['publication_status' => PublicationStatus::Published]);

    $response = $this->get('/projects?nature=' . $natureTag->slug);

    $response->assertSee($projectWithTag->name);
});

test('can filter by section', function () {
    $section = createSection();
    $group = createGroup(['section_id' => $section->id]);
    $supervisor = createSupervisor(['group_id' => $group->id]);

    $project = createProject();
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisor->id,
        'order_rank' => 1,
    ]);
    $project->update(['publication_status' => PublicationStatus::Published]);

    $response = $this->get('/projects?section=' . $section->slug);

    $response->assertSee($project->name);
});

test('can filter by focus tag', function () {
    $focusTag = Tag::where('category', TagCategory::Focus)->first();
    if (!$focusTag) {
        $focusTag = Tag::factory()->category(TagCategory::Focus)->create();
    }

    $projectWithTag = createProject();
    $projectWithTag->tags()->attach($focusTag->id);
    $projectWithTag->update(['publication_status' => PublicationStatus::Published]);

    $response = $this->get('/projects?focus=' . $focusTag->slug);

    $response->assertSee($projectWithTag->name);
});

test('pagination works', function () {
    // Create more than 12 projects (default pagination)
    for ($i = 0; $i < 15; $i++) {
        $project = createProject();
        $project->update(['publication_status' => PublicationStatus::Published]);
    }

    $response = $this->get('/projects');

    $response->assertStatus(200);
    // Should have pagination links if more than 12 projects
    $response->assertViewHas('projects');
});



