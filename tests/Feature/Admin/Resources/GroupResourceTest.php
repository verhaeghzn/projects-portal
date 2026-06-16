<?php

use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Models\Group;

beforeEach(function () {
    seedTestData();
    $this->user = authenticateAs(createSupervisor());
});

test('can list groups', function () {
    $group = Group::factory()->create();

    livewire(ListGroups::class)
        ->assertCanSeeTableRecords([$group]);
});

test('can create group', function () {
    $section = createSection();

    livewire(CreateGroup::class)
        ->fillForm([
            'name' => 'New Group',
            'section_id' => $section->id,
            'abbrev_id' => 'NEW',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Group::where('name', 'New Group')->exists())->toBeTrue();
});

test('can edit group', function () {
    $group = Group::factory()->create();

    livewire(\App\Filament\Resources\Groups\Pages\EditGroup::class, ['record' => $group->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Group',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->fresh()->name)->toBe('Updated Group');
});

test('can edit group search summary', function () {
    $group = Group::factory()->create();

    livewire(\App\Filament\Resources\Groups\Pages\EditGroup::class, ['record' => $group->getRouteKey()])
        ->fillForm([
            'search_summary' => 'Experimental mechanics group specializing in fatigue of metals.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->fresh()->search_summary)
        ->toBe('Experimental mechanics group specializing in fatigue of metals.');
});


