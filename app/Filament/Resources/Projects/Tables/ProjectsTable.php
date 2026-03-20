<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->disk('public')
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('types.name')
                    ->label('Types')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->types->pluck('name')->join(', ')),

                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('supervisors.avatar')
                    ->label('Supervisors')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->getStateUsing(function ($record) {
                        // Get all users that are supervisors in correct order, exclude externals
                        return $record->supervisorLinks
                            ->filter(fn ($link) => ! $link->isExternal())
                            ->map(function ($link) {
                                $supervisor = $link->supervisor;
                                if (! $supervisor) {
                                    return null;
                                }

                                // If avatar exists, return the URL
                                if ($supervisor->avatar_url) {
                                    return \Illuminate\Support\Facades\Storage::url($supervisor->avatar_url);
                                }

                                // Otherwise, generate an SVG data URL with the initial
                                $initial = strtoupper(substr($supervisor->name, 0, 1));
                                $svg = sprintf(
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#7fabc9"/><text x="20" y="20" font-family="Arial, sans-serif" font-size="16" font-weight="600" fill="white" text-anchor="middle" dominant-baseline="central">%s</text></svg>',
                                    htmlspecialchars($initial)
                                );

                                return 'data:image/svg+xml;base64,'.base64_encode($svg);
                            })
                            ->filter()
                            ->values()
                            ->toArray();
                    })
                    ->tooltip(function ($record) {
                        return $record->supervisorLinks
                            ->filter(fn ($link) => ! $link->isExternal())
                            ->map(fn ($link) => $link->supervisor?->name)
                            ->filter()
                            ->join(', ');
                    })
                    ->searchable(false),

                TextColumn::make('listing_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Concept' => 'gray',
                        'Taken' => 'warning',
                        'Available' => 'success',
                        default => 'gray',
                    })
                    ->getStateUsing(function ($record) {
                        if (! $record->is_published) {
                            return 'Concept';
                        }

                        if ($record->is_taken) {
                            return 'Taken';
                        }

                        return 'Available';
                    })
                    ->tooltip(fn ($record) => $record->is_taken
                        ? trim(collect([$record->student_name, $record->student_email])->filter()->join(' · '))
                        : null),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('scopes')
                    ->default('my_projects')
                    ->schema([
                        Select::make('ownership')
                            ->label('Ownership')
                            ->options([
                                'my_projects' => 'My Projects',
                                'group_projects' => 'Group Projects',
                                'all' => 'All Projects',
                            ])
                            ->default('my_projects'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['ownership'] === 'my_projects') {
                            $user = Auth::user();

                            return $query->where(function ($q) use ($user) {
                                $q->where('project_owner_id', $user->id)
                                    ->orWhereHas('supervisorLinks', function ($subQ) use ($user) {
                                        $subQ->where('supervisor_type', User::class)
                                            ->where('supervisor_id', $user->id);
                                    });
                            });
                        }
                        if ($data['ownership'] === 'group_projects') {
                            $user = Auth::user();
                            $groupUserIds = $user->group->users->pluck('id');

                            return $query->whereHas('supervisorLinks', function ($subQ) use ($groupUserIds) {
                                $subQ->where('supervisor_type', User::class)
                                    ->whereIn('supervisor_id', $groupUserIds);
                            });
                        }

                        return $query;
                    }),

                SelectFilter::make('types')
                    ->label('Type')
                    ->relationship('types', 'name')
                    ->multiple()
                    ->preload(),

                Filter::make('status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'taken' => 'Taken',
                                'concept' => 'Concept',
                            ])
                            ->default('available'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['status'] === 'available') {
                            return $query->whereNull('student_name')->whereNull('student_email');
                        }
                        if ($data['status'] === 'taken') {
                            return $query->where(function ($q) {
                                $q->whereNotNull('student_name')->orWhereNotNull('student_email');
                            });
                        }
                        if ($data['status'] === 'concept') {
                            return $query->where('is_published', false);
                        }

                        return $query;
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
