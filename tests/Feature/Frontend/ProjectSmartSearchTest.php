<?php

use App\Ai\Agents\ProjectSearchInterpreter;
use App\Models\Group;
use App\Models\Project;
use App\Models\ProjectSupervisor;
use App\Models\ProjectType;
use App\Models\User;
use Laravel\Ai\Prompts\AgentPrompt;

beforeEach(function () {
    seedTestData();
});

function attachThesisType($project, string $slug = 'master_thesis'): void
{
    $type = ProjectType::where('slug', $slug)->first();
    $project->types()->sync([$type->id]);
}

test('smart search filters projects using project ids from faked interpreter', function () {
    createSupervisor();

    $steel = createProject([
        'name' => 'Steel fatigue study',
        'short_description' => 'Metal experiments',
        'richtext_content' => '<p>Experimental fatigue testing on steel specimens.</p>',
        'is_published' => true,
    ]);
    attachThesisType($steel);

    $other = createProject([
        'name' => 'Polymer synthesis',
        'short_description' => 'Organic materials only',
        'richtext_content' => '<p>Organic chemistry synthesis project.</p>',
        'is_published' => true,
    ]);
    attachThesisType($other);

    ProjectSearchInterpreter::fake([[
        'matches' => [[
            'project_id' => $steel->id,
            'reason' => 'Focuses on experimental fatigue testing of steel specimens.',
        ]],
        'summary_for_user' => 'Searching for steel-related projects.',
    ]]);

    $response = $this->get('/projects?q='.rawurlencode('projects about steel').'&type=master_thesis');

    $response->assertStatus(200);
    $response->assertSee('Steel fatigue study', false);
    $response->assertDontSee('Polymer synthesis', false);
    $response->assertSee('Searching for steel-related projects.', false);

    ProjectSearchInterpreter::assertPrompted(fn (AgentPrompt $p) => str_contains($p->prompt, 'user_query')
        && str_contains($p->prompt, 'Steel fatigue study'));
});

test('smart search returns multiple projects from faked interpreter', function () {
    $supervisorA = createSupervisor(['name' => 'Supervisor Alpha']);
    $supervisorB = createSupervisor(['name' => 'Supervisor Beta']);

    $projectA = createProject([
        'name' => 'Alpha supervised project',
        'project_owner_id' => $supervisorA->id,
        'is_published' => true,
    ]);
    $projectA->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $projectA->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisorA->id,
        'order_rank' => 1,
    ]);
    attachThesisType($projectA);

    $projectB = createProject([
        'name' => 'Beta supervised project',
        'project_owner_id' => $supervisorB->id,
        'is_published' => true,
    ]);
    $projectB->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $projectB->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisorB->id,
        'order_rank' => 1,
    ]);
    attachThesisType($projectB);

    $unrelated = createProject([
        'name' => 'Someone else project',
        'is_published' => true,
    ]);
    attachThesisType($unrelated);

    ProjectSearchInterpreter::fake([[
        'matches' => [
            ['project_id' => $projectA->id, 'reason' => 'Supervised by Supervisor Alpha.'],
            ['project_id' => $projectB->id, 'reason' => 'Supervised by Supervisor Beta.'],
        ],
        'summary_for_user' => 'Either supervisor A or B.',
    ]]);

    $response = $this->get('/projects?q='.rawurlencode('either supervisor').'&type=master_thesis');

    $response->assertStatus(200);
    $response->assertSee('Alpha supervised project', false);
    $response->assertSee('Beta supervised project', false);
    $response->assertDontSee('Someone else project', false);
});

test('unknown project ids from interpreter are ignored', function () {
    $real = createSupervisor();
    $project = createProject([
        'name' => 'Listed under real supervisor',
        'project_owner_id' => $real->id,
        'is_published' => true,
    ]);
    $project->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $project->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $real->id,
        'order_rank' => 1,
    ]);
    attachThesisType($project);

    ProjectSearchInterpreter::fake([[
        'matches' => [[
            'project_id' => 999999,
            'reason' => 'Should be ignored.',
        ]],
        'summary_for_user' => 'Ignored bad id.',
    ]]);

    $response = $this->get('/projects?q=1&type=master_thesis');

    $response->assertStatus(200);
    $response->assertDontSee('Listed under real supervisor', false);
});

test('smart search scopes candidates to bachelor thesis type from navigation', function () {
    createSupervisor();

    $bachelor = createProject([
        'name' => 'Bachelor additive manufacturing',
        'short_description' => '3D printing for undergraduates',
        'is_published' => true,
    ]);
    attachThesisType($bachelor, 'bachelor_thesis');

    $master = createProject([
        'name' => 'Master additive manufacturing',
        'short_description' => '3D printing for graduates',
        'is_published' => true,
    ]);
    attachThesisType($master, 'master_thesis');

    ProjectSearchInterpreter::fake([[
        'matches' => [[
            'project_id' => $bachelor->id,
            'reason' => 'Undergraduate 3D printing project.',
        ]],
        'summary_for_user' => 'Bachelor 3D printing projects.',
    ]]);

    $response = $this->get('/projects?q='.rawurlencode('3D printing').'&type=bachelor_thesis');

    $response->assertStatus(200);
    $response->assertSee('Bachelor additive manufacturing', false);
    $response->assertDontSee('Master additive manufacturing', false);

    ProjectSearchInterpreter::assertPrompted(fn (AgentPrompt $p) => str_contains($p->prompt, 'bachelor_thesis')
        && ! str_contains($p->prompt, 'Master additive manufacturing'));
});

test('smart search supplements nature inspired experimental matches the llm may miss', function () {
    $supervisor = createSupervisor(['name' => 'Tommaso Magrini', 'slug' => 'tommaso-magrini']);
    $group = createGroup(['name' => 'Group Magrini']);
    $supervisor->update(['group_id' => $group->id]);

    Group::whereKey($group->id)->update([
        'search_summary' => 'Group Magrini focuses on bioinspired composites and nature-inspired architected materials with experimental testing.',
    ]);

    $bioProject = createProject([
        'name' => 'SLA Printing of Glass-Reinforced Composites',
        'short_description' => 'Design and additive manufacturing of bioinspired high-performance composites using SLA.',
        'richtext_content' => '<p>Experimental mechanical testing and fracture characterization.</p>',
        'is_published' => true,
        'project_owner_id' => $supervisor->id,
    ]);
    $bioProject->supervisorLinks()->delete();
    ProjectSupervisor::create([
        'project_id' => $bioProject->id,
        'supervisor_type' => User::class,
        'supervisor_id' => $supervisor->id,
        'order_rank' => 1,
    ]);
    attachThesisType($bioProject);

    Project::whereKey($bioProject->id)->update([
        'search_summary' => 'This project uses SLA additive manufacturing and experimental mechanical testing of high-performance composites.',
    ]);

    ProjectSearchInterpreter::fake([[
        'matches' => [],
        'summary_for_user' => 'No matches found.',
    ]]);

    $response = $this->get('/projects?q='.rawurlencode('Experiments with nature inspired materials').'&type=master_thesis');

    $response->assertStatus(200);
    $response->assertSee('SLA Printing of Glass-Reinforced Composites', false);
});
