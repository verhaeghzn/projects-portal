<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Arrayable;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        $raw = $this->form->getRawState();
        if ($raw instanceof Arrayable) {
            $raw = $raw->toArray();
        } else {
            $raw = (array) $raw;
        }

        if (! empty($raw['save_as_concept'])) {
            $data['is_published'] = false;
        }

        unset($data['save_as_concept']);

        return $data;
    }
}
