<?php

namespace App\Filament\Pages;

use App\Models\Role;
use App\Models\User;
use App\Notifications\UserInvited;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use UnitEnum;

class GroupMembers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Group Members';

    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.group-members';

    public function getTitle(): string | Htmlable
    {
        $group = auth()->user()?->group;
        return $group ? $group->name : 'Group Members';
    }

    public function table(Table $table): Table
    {
        $group = auth()->user()?->group;

        return $table
            ->query(
                $group
                    ? User::query()->where('group_id', $group->id)
                    : User::query()->whereRaw('1 = 0') // Empty query if no group
            )
            ->columns([

                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->badge()
                    ->separator(','),

                TextColumn::make('email_verified_at')
                    ->label('Status')
                    ->formatStateUsing(fn ($state, User $record): string =>
                        $record->invitation_token !== null && $record->email_verified_at === null
                            ? 'Pending Activation'
                            : ($state ? 'Activated' : 'Inactive')
                    )
                    ->description(fn ($state, User $record): ?string =>
                        $record->invitation_token !== null && $record->email_verified_at === null && $record->invitation_sent_at
                            ? 'Invite sent at ' . $record->invitation_sent_at->format('M j, Y g:i A')
                            : null
                    )
                    ->badge()
                    ->color(fn ($state, User $record): string =>
                        $record->invitation_token !== null && $record->email_verified_at === null
                            ? 'warning'
                            : ($state ? 'success' : 'gray')
                    )
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('No group members found')
            ->emptyStateDescription('Users in your group will appear here.')
            ->recordActions([
                Action::make('resendInvite')
                    ->label('Resend Invite')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->invitation_token !== null && $record->email_verified_at === null)
                    ->requiresConfirmation()
                    ->modalHeading('Resend Invitation')
                    ->modalDescription('Are you sure you want to resend the invitation email? A new invitation link will be generated.')
                    ->modalSubmitActionLabel('Resend Invite')
                    ->action(function (User $record) {
                        // Generate new invitation token
                        $invitationToken = Str::random(64);
                        
                        // Update user with new token and timestamp
                        $record->invitation_token = $invitationToken;
                        $record->invitation_sent_at = now();
                        $record->save();
                        
                        // Send invitation notification
                        $record->notify(new UserInvited($invitationToken));
                        
                        Notification::make()
                            ->title('Invitation Resent')
                            ->success()
                            ->body('A new invitation email has been sent to ' . $record->email)
                            ->send();
                    }),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        
        // Only show invite button for staff members and administrators
        if (!$user || !$user->hasAnyRole(['Administrator', 'Staff member - supervisor'])) {
            return [];
        }

        return [
            Action::make('invite')
                ->label('Invite new user')
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
                        ->unique('users', 'email')
                        ->validationMessages([
                            'unique' => 'This email address is already registered.',
                        ]),
                    Radio::make('role')
                        ->label('Role')
                        ->options([
                            'Staff member - supervisor' => 'Staff member',
                            'Researcher' => 'Researcher',
                        ])
                        ->default('Researcher')
                        ->required(),
                ])
                ->action(function (array $data) use ($user) {
                    // Check if email already exists
                    if (User::where('email', $data['email'])->exists()) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body('This email address is already registered.')
                            ->send();
                        return;
                    }

                    $invitationToken = Str::random(64);

                    $newUser = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(32)), // Temporary password
                        'group_id' => $user->group_id, // Attach to the staff member's group
                        'invitation_token' => $invitationToken,
                        'invitation_sent_at' => now(),
                    ]);

                    // Assign selected role
                    $role = Role::where('name', $data['role'])->first();
                    if ($role) {
                        $newUser->assignRole($role);
                    }

                    // Send invitation notification
                    $newUser->notify(new UserInvited($invitationToken));

                    Notification::make()
                        ->title('User Invited')
                        ->success()
                        ->body('An invitation email has been sent to ' . $data['email'])
                        ->send();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->group_id !== null;
    }
}

