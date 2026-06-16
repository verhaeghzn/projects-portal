<?php

namespace App\Filament\Resources\Groups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GroupForm
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
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('section_id')
                    ->label('Section')
                    ->relationship('section', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),

                Select::make('group_leader_id')
                    ->label('Group Leader')
                    ->relationship('leader', 'name')
                    ->searchable()
                    ->preload(),

                TextInput::make('external_url')
                    ->label('External URL')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Optional URL that will make the group name clickable on project detail pages.'),

                Section::make('Smart search')
                    ->description('Used by AI-powered project search to describe this group\'s research themes.')
                    ->schema([
                        Textarea::make('search_summary')
                            ->label('Search summary')
                            ->rows(4)
                            ->maxLength((int) config('ai.project_search.group_summary_max_chars', 500))
                            ->helperText(fn ($record) => filled($record?->search_summary_generated_at)
                                ? 'Last generated '.$record->search_summary_generated_at->timezone(config('app.timezone'))->format('j M Y, H:i').'.'
                                : 'Not yet generated. Use "Regenerate search summary" or run groups:generate-search-summaries.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visibleOn('edit'),
            ]);
    }
}
