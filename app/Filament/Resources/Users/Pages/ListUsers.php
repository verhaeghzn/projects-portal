<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Group;
use App\Models\User;
use App\Notifications\UserInvited;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;

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
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique('users', 'email'),
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
                    $invitationToken = Str::random(64);

                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(32)), // Temporary password
                        'invitation_token' => $invitationToken,
                        'invitation_sent_at' => now(),
                    ]);

                    // Assign selected roles
                    if (!empty($data['roles'])) {
                        $roles = Role::whereIn('name', $data['roles'])->get();
                        $user->assignRole($roles);
                    }

                    $user->notify(new UserInvited($invitationToken));

                    Notification::make()
                        ->title('User Invited')
                        ->success()
                        ->body('An invitation email has been sent to ' . $data['email'])
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
