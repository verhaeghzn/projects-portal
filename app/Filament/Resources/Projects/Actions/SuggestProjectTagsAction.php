<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Models\Tag;
use App\Services\ProjectTagSuggestionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Throwable;

class SuggestProjectTagsAction
{
    public static function make(): Action
    {
        return Action::make('suggestTags')
            ->label('Suggest tags')
            ->icon(Heroicon::OutlinedSparkles)
            ->iconButton()
            ->color('primary')
            ->tooltip('Automatically suggest tags using AI')
            ->action(function (Get $get, Set $set, Select $component, ProjectTagSuggestionService $service): void {
                $name = trim((string) $get('name'));
                $shortDescription = trim((string) $get('short_description'));
                $richtextContent = (string) $get('richtext_content');

                if ($name === '' || ($shortDescription === '' && blank(strip_tags($richtextContent)))) {
                    Notification::make()
                        ->title('Add a title and description first')
                        ->body('The AI needs at least a project title and a short or full description to suggest tags.')
                        ->warning()
                        ->send();

                    return;
                }

                try {
                    $suggestedTagIds = $service->suggest([
                        'name' => $name,
                        'short_description' => $shortDescription,
                        'richtext_content' => $richtextContent,
                        'types' => $get('types') ?? [],
                    ]);

                    if ($suggestedTagIds === null) {
                        Notification::make()
                            ->title('Tag suggestion unavailable')
                            ->body('Configure an OpenAI API key to use automatic tag suggestions.')
                            ->warning()
                            ->send();

                        return;
                    }

                    if ($suggestedTagIds === []) {
                        Notification::make()
                            ->title('No tags suggested')
                            ->body('The AI could not find matching tags for this project. Try adding more detail to the description.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $existingTagIds = collect($get($component->getName()) ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    $mergedTagIds = array_values(array_unique([
                        ...$existingTagIds,
                        ...$suggestedTagIds,
                    ]));

                    $set($component->getName(), $mergedTagIds);

                    $tagNames = Tag::query()
                        ->whereIn('id', $suggestedTagIds)
                        ->orderBy('name')
                        ->pluck('name')
                        ->join(', ');

                    Notification::make()
                        ->title('Tags suggested')
                        ->body("Added: {$tagNames}")
                        ->success()
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Tag suggestion failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
