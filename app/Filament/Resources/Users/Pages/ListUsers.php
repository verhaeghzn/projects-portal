<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Users\InviteDuplicateWarning;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserInvited;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('invite')
                ->label('Invite User')
                ->color('success')
                ->icon('heroicon-o-envelope')
                ->form([
                    InviteDuplicateWarning::notice(),
                    InviteDuplicateWarning::confirmation(),
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->live(debounce: 500),
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->live(debounce: 500)
                        ->rules([
                            fn (): Closure => InviteDuplicateWarning::emailAlreadyRegistered(),
                        ]),
                    Select::make('group_id')
                        ->label('Group')
                        ->options(Group::all()->pluck('name', 'id'))
                        ->required(),
                    CheckboxList::make('roles')
                        ->label('Roles')
                        ->options([
                            'Administrator' => 'Administrator',
                            'Staff member - supervisor' => 'Staff member - supervisor',
                            'Researcher' => 'Researcher',
                            'Support colleague' => 'Support colleague',
                        ])
                        ->default(['Researcher'])
                        ->required(),

                ])
                ->action(function (array $data) {
                    if ($message = InviteDuplicateWarning::blockReason($data)) {
                        Notification::make()
                            ->title('Check before inviting')
                            ->warning()
                            ->body($message)
                            ->send();

                        return;
                    }

                    $invitationToken = Str::random(64);

                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(32)), // Temporary password
                        'group_id' => $data['group_id'],
                        'invitation_token' => $invitationToken,
                        'invitation_sent_at' => now(),
                    ]);

                    // Assign selected roles
                    if (! empty($data['roles'])) {
                        $roles = Role::whereIn('name', $data['roles'])->get();
                        $user->assignRole($roles);
                    }

                    $user->notify(new UserInvited($invitationToken));

                    Notification::make()
                        ->title('User Invited')
                        ->success()
                        ->body('An invitation email has been sent to '.$data['email'])
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
