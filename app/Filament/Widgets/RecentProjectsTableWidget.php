<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectType;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentProjectsTableWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::query()
                    ->with(['owner.group.section', 'supervisors', 'tags', 'types'])
                    ->limit(5)
            )
            ->columns([
                ImageColumn::make('featured_image')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('name')
                    ->label('Project Name')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('types.name')
                    ->label('Types')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->types->pluck('name')->join(', '))
                    ->color('info'),

                    ImageColumn::make('supervisors.avatar')
                    ->label('Supervisors')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->getStateUsing(function ($record) {
                        // Get all users that are supervisors in correct order, exclude externals
                        return $record->supervisorLinks
                            ->filter(fn($link) => !$link->isExternal())
                            ->map(function($link) {
                                $supervisor = $link->supervisor;
                                if (!$supervisor) {
                                    return null;
                                }
                                
                                // If avatar exists, return the URL
                                if ($supervisor->avatar_url) {
                                    return \Illuminate\Support\Facades\Storage::url($supervisor->avatar_url);
                                }
                                
                                // Otherwise, generate an SVG data URL with the initial
                                $initial = strtoupper(substr($supervisor->name, 0, 1));
                                $svg = sprintf(
                                    '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><circle cx="20" cy="20" r="20" fill="#C72026"/><text x="20" y="20" font-family="Arial, sans-serif" font-size="16" font-weight="600" fill="white" text-anchor="middle" dominant-baseline="central">%s</text></svg>',
                                    htmlspecialchars($initial)
                                );
                                return 'data:image/svg+xml;base64,' . base64_encode($svg);
                            })
                            ->filter()
                            ->values()
                            ->toArray();
                    })
                    ->tooltip(function ($record) {
                        return $record->supervisorLinks
                            ->filter(fn($link) => !$link->isExternal())
                            ->map(fn($link) => $link->supervisor?->name)
                            ->filter()
                            ->join(', ');
                    })
                    ->searchable(false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('Recent Projects')
            ->description('Latest projects added to the system')
            ->recordUrl(fn (Project $record): string => route('projects.show', $record->slug), true);
    }
}
