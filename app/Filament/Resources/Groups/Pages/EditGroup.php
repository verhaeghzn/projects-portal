<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Services\GroupSearchSummaryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateSearchSummary')
                ->label('Regenerate search summary')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->visible(fn () => app(GroupSearchSummaryService::class)->projectsQuery($this->getRecord())->exists())
                ->action(function (GroupSearchSummaryService $summaryService): void {
                    try {
                        $summary = $summaryService->generateFor($this->getRecord()->fresh());

                        if ($summary === null) {
                            Notification::make()
                                ->title('No search summary produced')
                                ->warning()
                                ->send();

                            return;
                        }

                        $this->refreshFormData([
                            'search_summary',
                            'search_summary_generated_at',
                        ]);

                        Notification::make()
                            ->title('Search summary regenerated')
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Search summary generation failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
