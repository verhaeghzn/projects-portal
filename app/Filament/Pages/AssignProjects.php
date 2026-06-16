<?php

namespace App\Filament\Pages;

use App\Models\Division;
use App\Models\Project;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AssignProjects extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Projects';

    protected static ?string $navigationLabel = 'Assign projects';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.assign-projects';

    public function getTitle(): string|Htmlable
    {
        $division = $this->getUserDivision();

        if ($division === null) {
            return 'Assign projects';
        }

        return 'Assign projects – '.($division->abbrev ?? $division->name);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getProjectsQuery())
            ->columns([
                TextColumn::make('project_number')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Project')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('section.name')
                    ->label('Section')
                    ->sortable(),

                TextColumn::make('types.name')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (Project $record): string => $record->types->pluck('name')->join(', ')),

                TextColumn::make('supervisors')
                    ->label('Supervisors')
                    ->getStateUsing(function (Project $record): string {
                        return $record->supervisorLinks
                            ->map(fn ($link) => $link->name)
                            ->filter()
                            ->join(', ');
                    })
                    ->wrap()
                    ->limit(40),

                TextInputColumn::make('student_name')
                    ->label('Student name')
                    ->placeholder('Name')
                    ->rules(['nullable', 'string', 'max:255'])
                    ->disabled(fn (Project $record): bool => ! auth()->user()?->can('update', $record))
                    ->afterStateUpdated(fn () => $this->notifySaved()),

                TextInputColumn::make('student_email')
                    ->label('Student email')
                    ->type('email')
                    ->placeholder('Email')
                    ->rules(['nullable', 'email', 'max:255'])
                    ->disabled(fn (Project $record): bool => ! auth()->user()?->can('update', $record))
                    ->afterStateUpdated(fn () => $this->notifySaved()),
            ])
            ->defaultSort('project_number')
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No open projects')
            ->emptyStateDescription('All published projects in your division have been assigned, or none are available yet.')
            ->searchPlaceholder('Search by number or title…');
    }

    protected function getProjectsQuery(): Builder
    {
        $query = Project::query()
            ->with([
                'supervisorLinks.supervisor.group.section',
                'types',
            ])
            ->available()
            ->where('is_published', true);

        $divisionId = $this->getUserDivisionId();

        if ($divisionId !== null) {
            $query->inDivision($divisionId);
        }

        return $query;
    }

    protected function notifySaved(): void
    {
        Notification::make()
            ->title('Student assigned')
            ->success()
            ->send();
    }

    protected function getUserDivision(): ?Division
    {
        return auth()->user()?->group?->section?->division;
    }

    protected function getUserDivisionId(): ?int
    {
        $user = auth()->user();

        if ($user?->hasRole('Administrator')) {
            return null;
        }

        return $user?->group?->section?->division_id;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null || ! $user->can('viewAny', Project::class)) {
            return false;
        }

        if ($user->hasRole('Administrator')) {
            return true;
        }

        if ($user->hasRole('Support colleague')) {
            return $user->group?->section?->division_id !== null;
        }

        return false;
    }
}
