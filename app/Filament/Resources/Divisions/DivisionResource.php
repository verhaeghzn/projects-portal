<?php

namespace App\Filament\Resources\Divisions;

use App\Filament\Resources\Divisions\Pages\CreateDivision;
use App\Filament\Resources\Divisions\Pages\EditDivision;
use App\Filament\Resources\Divisions\Pages\ListDivisions;
use App\Models\Division;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DivisionResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Administrator') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Display name, e.g. "Computational and Experimental Mechanics (CEM)". Slug for URLs is generated automatically.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->color('gray'),
                \Filament\Tables\Columns\TextColumn::make('sections_count')
                    ->counts('sections')
                    ->label('Sections')
                    ->badge()
                    ->color('primary'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDivisions::route('/'),
            'create' => CreateDivision::route('/create'),
            'edit' => EditDivision::route('/{record}/edit'),
        ];
    }
}
