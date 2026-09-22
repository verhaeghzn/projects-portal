<?php

use App\Filament\Pages\GroupMembers;
use App\Models\User;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    seedTestData();
});

test('group invite warns about a similar account before creating one', function () {
    Notification::fake();

    $group = createGroup();
    authenticateAs(createSupervisor(['group_id' => $group->id]));

    User::factory()->create([
        'name' => 'Existing Person',
        'email' => 'a.jansen@student.tue.nl',
        'group_id' => $group->id,
    ]);

    livewire(GroupMembers::class)
        ->mountAction('invite')
        ->assertMountedActionModalSee('Check before inviting')
        ->fillForm([
            'name' => 'A Jansen',
            'email' => 'a.jansen@tue.nl',
            'role' => 'Researcher',
        ])
        ->assertMountedActionModalSee('a.jansen@student.tue.nl')
        ->callMountedAction()
        ->assertHasFormErrors(['confirm_not_duplicate']);

    expect(User::where('email', 'a.jansen@tue.nl')->exists())->toBeFalse();

    livewire(GroupMembers::class)
        ->callAction('invite', [
            'name' => 'A Jansen',
            'email' => 'a.jansen@tue.nl',
            'role' => 'Researcher',
            'confirm_not_duplicate' => true,
        ])
        ->assertHasNoFormErrors();

    $invited = User::where('email', 'a.jansen@tue.nl')->first();

    expect($invited)->not->toBeNull()
        ->and($invited->group_id)->toBe($group->id);

    Notification::assertSentTo($invited, UserInvited::class);
});
