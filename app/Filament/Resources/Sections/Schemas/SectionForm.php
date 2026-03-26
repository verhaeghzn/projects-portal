<?php

namespace App\Filament\Resources\Sections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('abbrev_id')
                    ->label('Abbreviation ID')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true)
                    ->helperText('Short identifier used in project numbers (for example: MOM, PP, ET).'),

                Select::make('division_id')
                    ->label('Division')
                    ->relationship('division', 'name')
                    ->preload()
                    ->searchable()
                    ->helperText('The division this section belongs to (e.g. CEM, TFE, DSD). Projects are shown under a division based on their section.'),
            ]);
    }
}
