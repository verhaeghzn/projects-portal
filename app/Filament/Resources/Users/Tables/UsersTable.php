<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use App\Notifications\UserInvited;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->badge()
                    ->separator(','),

                TextColumn::make('group.name')
                    ->label('Group')
                    ->searchable()
                    ->sortable()
                    ->badge(),

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
            ->filters([
                Filter::make('invited_sent')
                    ->form([
                        Select::make('invited_within')
                            ->label('Invite sent')
                            ->placeholder('All users')
                            ->options([
                                '7' => 'Past 7 days',
                                '14' => 'Past 14 days',
                                '30' => 'Past 30 days',
                                '90' => 'Past 90 days',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $days = $data['invited_within'] ?? null;
                        if ($days === null || $days === '') {
                            return $query;
                        }
                        $since = now()->subDays((int) $days);
                        return $query
                            ->whereNotNull('invitation_token')
                            ->whereNull('email_verified_at')
                            ->where('invitation_sent_at', '>=', $since);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
