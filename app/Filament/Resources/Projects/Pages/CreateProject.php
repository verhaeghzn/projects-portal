<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Arrayable;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

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
